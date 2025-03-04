import mysql.connector
import random
import time
from datetime import datetime




def Envois_mesures(capteur,valeur,unite,date):
    DATABASE_HOST = '10.0.14.4'  
    DATABASE_NAME = 'eau_pure'  
    DATABASE_USER = 'root'  
    DATABASE_PASSWORD = 'ieufdl'
    DATABASE_PORT = '9999'  # Port mysql ouvert sur le serveur


    # Connexion BDD
    conn = mysql.connector.connect(
        host=DATABASE_HOST,
        database=DATABASE_NAME,
        user=DATABASE_USER,
        password=DATABASE_PASSWORD,
        port=DATABASE_PORT,
    )
    cursor = conn.cursor()
    
    cursor.execute('''
    INSERT INTO Mesure (capteur, valeur, unite, date)
    VALUES (%s, %s, %s, %s)
    ''', (capteur,valeur,unite,date))

    print(capteur,valeur,unite)
date = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
Envois_mesures(1,15.2,"mm",date)