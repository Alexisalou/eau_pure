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



db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': 'ieufdl',
    'database': 'eau_pure',
    'port': '9999'
}



