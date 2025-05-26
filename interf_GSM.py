import mysql.connector
from datetime import datetime
from huawei_lte_api.Client import Client
from huawei_lte_api.Connection import Connection

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

def verifier_et_alert(db_config, huawei_api):
    try:
        seuil_pluviometre, seuil_limnimetre = lire_seuils(db_config)

        db = mysql.connector.connect(
            host=db_config['host'],
            user=db_config['user'],
            password=db_config['password'],
            database=db_config['database'],
            port=int(db_config['port'])
        )
        cursor = db.cursor(dictionary=True)

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

        alertes_par_technicien = {}

        for m in mesures:
            ref = m['reference'].upper()
            valeur = m['valeur']
            seuil = seuil_pluviometre if ref == 'PLUVIOMETRE' else seuil_limnimetre
            if valeur > seuil:
                tech_id = m['technicien_id']
                phone = m['numero_de_telephone']
                msg = f"Station {m['station_id']} – {ref} = {valeur} (seuil {seuil})"

                if tech_id not in alertes_par_technicien:
                    alertes_par_technicien[tech_id] = {
                        'phone': phone,
                        'messages': []
                    }
                alertes_par_technicien[tech_id]['messages'].append(msg)

        for tech_id, info in alertes_par_technicien.items():
            full_msg = " ; ".join(info['messages'])
            huawei_api.send_sms(info['phone'], f"Technicien {tech_id} : {full_msg}")

        if not alertes_par_technicien:
            print(f"[{datetime.now()}] Toutes les stations sont dans les seuils.")

    except Exception as e:
        print(f"Erreur dans la vérification: {e}")
