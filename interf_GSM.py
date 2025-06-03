import mysql.connector
from datetime import datetime
from huawei_lte_api.Client import Client
from huawei_lte_api.Connection import Connection
import time

# Classe pour gérer l'accès au modem Huawei (connexion, envoi de SMS)
class HuaweiApi:
    def __init__(self, adresse_modem='http://192.168.8.1'):
        self.adresse_modem = adresse_modem
        self.connexion = Connection(adresse_modem)
        self.client = None

    def __enter__(self):
        self.connexion.__enter__()
        self.client = Client(self.connexion)
        return self

    def __exit__(self, type_exc, valeur_exc, trace_exc):
        self.connexion.__exit__(type_exc, valeur_exc, trace_exc)

    def envoyer_sms(self, numero, texte):
        if not self.client:
            raise RuntimeError("API Huawei non initialisée")
        try:
            reponse = self.client.sms.send_sms(numero, texte)
            print(f"SMS envoyé à {numero} : {texte} (Réponse API : {reponse})")
        except Exception as e:
            print(f"Erreur envoi SMS à {numero} : {e}")

# Fonction principale de vérification des capteurs et d'alerte
def verifier_et_alerter(config_bdd, modem_huawei):
    try:
        base_donnees = mysql.connector.connect(
            host=config_bdd['host'],
            user=config_bdd['user'],
            password=config_bdd['password'],
            database=config_bdd['database'],
            port=int(config_bdd['port'])
        )
        curseur = base_donnees.cursor(dictionary=True)

        requete = """
            SELECT 
                t.id AS technicien_id, 
                t.numero_de_telephone, 
                s.id AS station_id, 
                c.reference, 
                m.valeur,
                p.seuil_pluviometre,
                p.seuil_limnimetre
            FROM Station s
            JOIN Preleveur p ON p.station = s.id
            JOIN Technicien t ON s.technicien = t.id
            JOIN Capteur c ON c.station = s.id
            JOIN (
                SELECT capteur, MAX(date) AS max_date
                FROM Mesure
                GROUP BY capteur
            ) dernier ON dernier.capteur = c.id
            JOIN Mesure m ON m.capteur = c.id AND m.date = dernier.max_date
            WHERE c.reference IN ('PLUVIOMETRE', 'LIMNIMETRE')
        """
        curseur.execute(requete)
        mesures = curseur.fetchall()
        curseur.close()
        base_donnees.close()

        alertes = {}

        for mesure in mesures:
            type_capteur = mesure['reference'].upper()
            valeur = mesure['valeur']
            
            # Ajustement des seuils
            if type_capteur == 'PLUVIOMETRE':
                seuil = mesure['seuil_pluviometre'] * 100  # <-- FACTEUR A AJUSTER
            else:
                seuil = mesure['seuil_limnimetre']

            if seuil is None:
                print(f"⚠️ Seuil manquant pour la station {mesure['station_id']}, capteur {type_capteur}")
                continue

            if valeur > seuil:
                id_technicien = mesure['technicien_id']
                telephone = mesure['numero_de_telephone']
                id_station = mesure['station_id']

                message_alerte = (
                    f"\n--- Station {id_station} ---\n"
                    f"Capteur  : {type_capteur}\n"
                    f"Mesure   : {valeur:.2f}\n"
                    f"Seuil    : {seuil:.2f}"
                )

                if id_technicien not in alertes:
                    alertes[id_technicien] = {
                        'telephone': telephone,
                        'messages': []
                    }
                alertes[id_technicien]['messages'].append(message_alerte)

        for id_technicien, infos in alertes.items():
            titre = f"ALERTE DE SEUIL - Technicien {id_technicien}"
            corps = "\n".join(infos['messages'])
            horodatage = datetime.now().strftime('%d/%m/%Y %H:%M')
            message_complet = f"{titre}\n{corps}\nHeure : {horodatage}"

            modem_huawei.envoyer_sms(infos['telephone'], message_complet)
            time.sleep(2)  # Pause 2 secondes entre chaque SMS

        if not alertes:
            print(f"[{datetime.now()}] ✅ Toutes les stations sont dans les seuils.")

    except Exception as e:
        print(f"❌ Erreur dans la vérification : {e}")
