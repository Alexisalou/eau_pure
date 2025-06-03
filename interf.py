import mysql.connector
import random
from datetime import datetime
DATABASE_HOST = ''  
DATABASE_NAME = ''  
DATABASE_USER = ''  
DATABASE_PASSWORD = ''
DATABASE_PORT =   # port doit être un int

def get_sensor_id(station_id, reference):
    try:
        conn = mysql.connector.connect(
            host=DATABASE_HOST,
            database=DATABASE_NAME,
            user=DATABASE_USER,
            password=DATABASE_PASSWORD,
            port=DATABASE_PORT,
        )
        cursor = conn.cursor()
        query = "SELECT id FROM Capteur WHERE station = %s AND reference = %s"
        cursor.execute(query, (station_id, reference))
        result = cursor.fetchone()
        cursor.close()
        conn.close()
        if result:
            return result[0]
        else:
            raise ValueError(f"Capteur non trouvé pour station={station_id} et reference='{reference}'")
    except mysql.connector.Error as err:
        print(f"Erreur base de données: {err}")
        return None

# Récupérer dynamiquement les IDs
PLUVIOMETER_SENSOR_ID = get_sensor_id(1, 'PLUVIOMETRE')
LIMNIMETER_SENSOR_ID = get_sensor_id(1, 'LIMNIMETRE')


DATABASE_HOST = '10.0.14.4'  
DATABASE_NAME = 'eau_pure'  
DATABASE_USER = 'root'  
DATABASE_PASSWORD = 'ieufdl'
DATABASE_PORT = '9999'  




def Envois_mesures(capteur, valeur, unite, date):
    try:
        # Connexion à la BDD
        conn = mysql.connector.connect(
            host=DATABASE_HOST,
            database=DATABASE_NAME,
            user=DATABASE_USER,
            password=DATABASE_PASSWORD,
            port=DATABASE_PORT,
        )
        cursor = conn.cursor()
        
        # Insertion des données
        cursor.execute('''
        INSERT INTO Mesure (capteur, valeur, unite, date)
        VALUES (%s, %s, %s, %s)
        ''', (capteur, valeur, unite, date))
        
        # Valider la transaction
        conn.commit()
        
        print(f"Mesure insérée: {capteur}, {valeur}, {unite}, {date}")
        
    except mysql.connector.Error as err:
        print(f"Erreur: {err}")
        
    finally:
        # Fermer la connexion
        if conn.is_connected():
            cursor.close()
            conn.close()

def lire_seuils(db_config):
    db = mysql.connector.connect(
        host=db_config['host'],
        user=db_config['user'],
        password=db_config['password'],
        database=db_config['database'],
        port=db_config['port']
    )
    cursor = db.cursor()
    
    # Récupérer le seuil du pluviomètre
    cursor.execute("SELECT seuil_pluviometre FROM Preleveur")
    seuil_pluviometre = cursor.fetchone()
    
    # Récupérer le seuil du limnimètre
    cursor.execute("SELECT seuil_limnimetre FROM Preleveur")
    seuil_limnimetre = cursor.fetchone()
    
    db.close()
    
    if seuil_pluviometre and seuil_limnimetre:
        return seuil_pluviometre[0], seuil_limnimetre[0]
    else:
        raise ValueError("Les seuils nécessaires ne sont pas disponibles.")

def lire_mesures(db_config):
    db = mysql.connector.connect(
        host=db_config['host'],
        user=db_config['user'],
        password=db_config['password'],
        database=db_config['database'],
        port=db_config['port']
    )
    cursor = db.cursor()
    
    # Récupérer la dernière mesure du pluviomètre
    cursor.execute("SELECT valeur FROM Mesure WHERE capteur = %s ORDER BY date DESC LIMIT 1", (PLUVIOMETER_SENSOR_ID,))
    mesure_pluviometre = cursor.fetchone()
    
    # Récupérer la dernière mesure du limnimètre
    cursor.execute("SELECT valeur FROM Mesure WHERE capteur = %s ORDER BY date DESC LIMIT 1", (LIMNIMETER_SENSOR_ID,))
    mesure_limnimetre = cursor.fetchone()
    
    db.close()
    
    if mesure_pluviometre and mesure_limnimetre:
        return mesure_pluviometre[0], mesure_limnimetre[0]
    else:
        raise ValueError("Les mesures nécessaires ne sont pas disponibles.")


