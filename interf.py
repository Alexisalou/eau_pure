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
    conn = None
    cursor = None
    try:
        # Conversion avant connexion, pour attraper l'erreur tôt
        valeur = float(valeur)
    except ValueError as ve:
        print(f"Erreur conversion valeur en float: {ve}")
        return  # Quitter la fonction car la valeur est invalide

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

        conn.commit()
        print(f"Mesure insérée: {capteur}, {valeur}, {unite}, {date}")

    except mysql.connector.Error as err:
        print(f"Erreur MySQL: {err}")
    except Exception as e:
        print(f"Erreur: {e}")
    finally:
        if cursor:
            cursor.close()
        if conn and conn.is_connected():
            conn.close()


db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': 'ieufdl',
    'database': 'eau_pure',
    'port': '9999'
}



