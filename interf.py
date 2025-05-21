import mysql.connector
import random
from datetime import datetime

DATABASE_HOST = '10.0.14.4'  
DATABASE_NAME = 'eau_pure'  
DATABASE_USER = 'root'  
DATABASE_PASSWORD = 'ieufdl'
DATABASE_PORT = '9999'  # Port mysql ouvert sur le serveur

PLUVIOMETER_SENSOR_ID = 1
LIMNIMETER_SENSOR_ID = 2

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
        
        valeur = float(valeur)  # Lève une ValueError si 'valeur' n'est pas un float

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
    
    # Requête seuil pluviomètre
    cursor.execute("SELECT seuil_pluviometre FROM Preleveur")
    seuil_pluviometre_result = cursor.fetchone()

    # Requête seuil limnimètre
    cursor.execute("SELECT seuil_limnimetre FROM Preleveur")
    seuil_limnimetre_result = cursor.fetchone()

    db.close()

    # Vérifie les résultats
    if not seuil_pluviometre_result or seuil_pluviometre_result[0] is None:
        raise ValueError("Seuil pluviomètre manquant ou nul")

    if not seuil_limnimetre_result or seuil_limnimetre_result[0] is None:
        raise ValueError("Seuil limnimètre manquant ou nul")

    return seuil_pluviometre_result[0], seuil_limnimetre_result[0]

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