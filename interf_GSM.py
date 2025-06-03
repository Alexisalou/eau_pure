import mysql.connector
from datetime import datetime
from huawei_lte_api.Client import Client
from huawei_lte_api.Connection import Connection

# Cette fonction lit les seuils depuis la base de données (pluviomètre et limnimètre)
def lire_seuils(db_config):
    try:
        db = mysql.connector.connect(
            host=db_config['host'],
            user=db_config['user'],
            password=db_config['password'],
            database=db_config['database'],
            port=int(db_config['port'])
        )
        cursor = db.cursor()
        cursor.execute("SELECT seuil_pluviometre, seuil_limnimetre FROM Preleveur LIMIT 1")
        result = cursor.fetchone()
        db.close()

        # Si on ne récupère rien ou que les seuils sont vides, on lève une erreur
        if not result or result[0] is None or result[1] is None:
            raise ValueError("Seuils manquants ou nuls")
        return result[0], result[1]

    except mysql.connector.Error as err:
        raise RuntimeError(f"Erreur lecture seuils: {err}")

# Classe pour gérer l'accès au modem Huawei (connexion, envoi de SMS)
class HuaweiApi:
    def __init__(self, base_url='http://192.168.8.1'):
        self.base_url = base_url
        self.connection = Connection(base_url)
        self.client = None

    # Gestion de contexte pour ouvrir proprement la connexion
    def __enter__(self):
        self.connection.__enter__()
        self.client = Client(self.connection)
        return self

    # Fermeture propre de la connexion
    def __exit__(self, exc_type, exc_val, exc_tb):
        self.connection.__exit__(exc_type, exc_val, exc_tb)

    # Fonction pour envoyer un SMS via le modem
    def send_sms(self, phone, message):
        if not self.client:
            raise RuntimeError("API Huawei non initialisée")
        try:
            response = self.client.sms.send_sms(phone, message)
            print(f"SMS envoyé à {phone}: {message} (Réponse API: {response})")
        except Exception as e:
            print(f"Erreur envoi SMS à {phone}: {e}")

# Fonction principale de vérification des capteurs et d'alerte
def verifier_et_alert(db_config, huawei_api):
    try:
        # Récupération des seuils de référence
        seuil_pluviometre, seuil_limnimetre = lire_seuils(db_config)

        # Connexion à la BDD pour récupérer les dernières mesures
        db = mysql.connector.connect(
            host=db_config['host'],
            user=db_config['user'],
            password=db_config['password'],
            database=db_config['database'],
            port=int(db_config['port'])
        )
        cursor = db.cursor(dictionary=True)

        # Cette requête récupère les dernières mesures des capteurs PLUVIOMETRE et LIMNIMETRE
        requete = """
            SELECT t.id AS technicien_id, t.numero_de_telephone, s.id AS station_id, c.reference, m.valeur
            FROM Station s
            JOIN Technicien t ON s.technicien = t.id
            JOIN Capteur c ON c.station = s.id
            JOIN (
                SELECT capteur, MAX(date) AS max_date
                FROM Mesure
                GROUP BY capteur
            ) latest ON latest.capteur = c.id
            JOIN Mesure m ON m.capteur = c.id AND m.date = latest.max_date
            WHERE c.reference IN ('PLUVIOMETRE', 'LIMNIMETRE')
        """
        cursor.execute(requete)
        mesures = cursor.fetchall()
        cursor.close()
        db.close()

        # On prépare un dictionnaire pour regrouper les alertes par technicien
        alertes_par_technicien = {}

        for m in mesures:
            ref = m['reference'].upper()
            valeur = m['valeur']
            seuil = seuil_pluviometre if ref == 'PLUVIOMETRE' else seuil_limnimetre

            # Si la valeur dépasse le seuil, on prépare un message d'alerte
            if valeur > seuil:
                tech_id = m['technicien_id']
                phone = m['numero_de_telephone']
                station = m['station_id']

                ligne = (
                    f"\n--- Station {station} ---\n"
                    f"Capteur  : {ref}\n"
                    f"Mesure   : {valeur:.2f}\n"
                    f"Seuil    : {seuil:.2f}"
                )

                # Si c’est la première alerte pour ce technicien, on initialise sa fiche
                if tech_id not in alertes_par_technicien:
                    alertes_par_technicien[tech_id] = {
                        'phone': phone,
                        'messages': []
                    }
                alertes_par_technicien[tech_id]['messages'].append(ligne)

        # On envoie les SMS regroupés par technicien
        for tech_id, info in alertes_par_technicien.items():
            header = f"ALERTE DE SEUIL - Technicien {tech_id}"
            body = "\n".join(info['messages'])
            timestamp = datetime.now().strftime('%d/%m/%Y %H:%M')
            full_msg = f"{header}\n{body}\nHeure : {timestamp}"
            huawei_api.send_sms(info['phone'], full_msg)

        # Si aucune alerte n’a été détectée, on l’indique en console
        if not alertes_par_technicien:
            print(f"[{datetime.now()}] ✅ Toutes les stations sont dans les seuils.")

    except Exception as e:
        print(f"❌ Erreur dans la vérification: {e}")
