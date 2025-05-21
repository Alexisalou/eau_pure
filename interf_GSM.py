import mysql.connector
from datetime import datetime
from huawei_lte_api.Client import Client
from huawei_lte_api.Connection import Connection

def recuperer_technicien_mesure_par_station(db_config):
    try:
        db = mysql.connector.connect(
            host=db_config['host'],
            user=db_config['user'],
            password=db_config['password'],
            database=db_config['database'],
            port=int(db_config['port'])
        )
        cursor = db.cursor(dictionary=True)

        requete = """
            SELECT DISTINCT Technicien.*, Station.id as station_id, Capteur.reference, Mesure.valeur
            FROM Mesure
            JOIN Capteur ON Mesure.capteur = Capteur.id
            JOIN Station ON Capteur.station = Station.id
            JOIN Technicien ON Station.technicien = Technicien.id
            WHERE Mesure.date = (
                SELECT MAX(date) FROM Mesure AS M2 
                WHERE M2.capteur = Mesure.capteur
            )
        """
        cursor.execute(requete)
        resultats = cursor.fetchall()
        db.close()
        return resultats

    except mysql.connector.Error as err:
        raise RuntimeError(f"Erreur de connexion ou d'exécution SQL : {err}")

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

        if not result or result[0] is None or result[1] is None:
            raise ValueError("Seuils manquants ou nuls")

        return result[0], result[1]

    except mysql.connector.Error as err:
        raise RuntimeError(f"Erreur lecture seuils: {err}")

class HuaweiApi:
    def __init__(self, base_url='http://192.168.8.1'):
        self.base_url = base_url
        self.connection = Connection(base_url)
        self.client = None

    def __enter__(self):
        self.connection.__enter__()
        self.client = Client(self.connection)
        return self

    def __exit__(self, exc_type, exc_val, exc_tb):
        self.connection.__exit__(exc_type, exc_val, exc_tb)

    def send_sms(self, phone, message):
        if not self.client:
            raise RuntimeError("API Huawei non initialisée")
        try:
            response = self.client.sms.send_sms(phone, message)
            print(f"SMS envoyé à {phone}: {message} (Réponse API: {response})")
        except Exception as e:
            print(f"Erreur envoi SMS à {phone}: {e}")
def recuperer_technicien_mesure(db_config):
    """
    Récupère la dernière mesure par capteur (PLUVIOMETRE, LIMNIMETRE) associée à chaque technicien.
    Renvoie une liste de dicts avec :
    - 'id' : id technicien
    - 'numero_de_telephone' : téléphone technicien
    - 'reference' : référence capteur
    - 'valeur' : valeur mesurée (la dernière)
    """
    try:
        db = mysql.connector.connect(
            host=db_config['host'],
            user=db_config['user'],
            password=db_config['password'],
            database=db_config['database'],
            port=db_config['port']
        )
        cursor = db.cursor(dictionary=True)

        # Requête pour récupérer la dernière mesure par capteur et technicien
        requete = """
            SELECT t.id, t.numero_de_telephone, c.reference, m.valeur
            FROM Technicien t
            JOIN Station s ON s.technicien = t.id
            JOIN Capteur c ON c.station = s.id
            JOIN Mesure m ON m.capteur = c.id
            INNER JOIN (
                -- Sous-requête pour récupérer la dernière date par capteur
                SELECT capteur, MAX(date) AS max_date
                FROM Mesure
                GROUP BY capteur
            ) lm ON lm.capteur = m.capteur AND lm.max_date = m.date
            WHERE c.reference IN ('PLUVIOMETRE', 'LIMNIMETRE')
        """

        cursor.execute(requete)
        resultats = cursor.fetchall()
        db.close()
        return resultats

    except mysql.connector.Error as err:
        raise RuntimeError(f"Erreur de connexion ou d'exécution SQL : {err}")
def verifier_et_alert(db_config, huawei_api):
    try:
        seuil_pluviometre, seuil_limnimetre = lire_seuils(db_config)
        data = recuperer_technicien_mesure(db_config)  # ta fonction qui renvoie list de dicts
        
        alertes_par_technicien = {}

        for ligne in data:
            ref = ligne['reference'].upper()
            valeur = ligne['valeur']
            seuil = None
            
            if ref == 'PLUVIOMETRE':
                seuil = seuil_pluviometre
            elif ref == 'LIMNIMETRE':
                seuil = seuil_limnimetre
            
            if seuil is None:
                continue

            if valeur > seuil:
                technicien_id = ligne['id']
                phone = ligne.get('numero_de_telephone')

                if not phone:
                    print(f"Technicien {technicien_id} sans téléphone, alerte non envoyée.")
                    continue

                msg = f"ALERTE: {ref} dépasse seuil ({valeur} > {seuil})"

                if technicien_id not in alertes_par_technicien:
                    alertes_par_technicien[technicien_id] = {
                        'phone': phone,
                        'messages': []
                    }
                alertes_par_technicien[technicien_id]['messages'].append(msg)

        if alertes_par_technicien:
            for tech_id, info in alertes_par_technicien.items():
                message_alerte = " ; ".join(info['messages'])
                huawei_api.send_sms(info['phone'], f"Technicien ID {tech_id} : {message_alerte}")
        else:
            print(f"[{datetime.now()}] Pas d'alerte : toutes les mesures sont sous seuils.")

    except Exception as e:
        print(f"Erreur dans la vérification: {e}")