import mysql.connector
import random
import time
from datetime import datetime

# Information de connexion a la BDD

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

# Fonction qui génère des valeurs factices
def generate_fake_data():
    pluviometer_value = round(random.uniform(0, 20), 2)  # Valeur aléatoire pour le pluviomètre 
    limnimeter_value = round(random.uniform(0, 10), 2)    # Valeur aléatoire pour le limnimètre 
    return pluviometer_value, limnimeter_value

# Fonction pour insérer les données factices dans la BDD
def insert_fake_data():
    pluviometer_value, limnimeter_value = generate_fake_data()
    current_time = datetime.now().strftime('%Y-%m-%d %H:%M:%S')  
    
    # Insertion des données du pluviomètre
    cursor.execute('''
    INSERT INTO Mesure (capteur, valeur, unite, date)
    VALUES (%s, %s, %s, %s)
    ''', (1,pluviometer_value, 'L/m²', current_time))  #Capteur numéro 1, valeur généré aléatoirement, unité et la date.
    
    # Insertion des données du limnimètre
    cursor.execute('''
    INSERT INTO Mesure (capteur, valeur, unite, date)
    VALUES (%s, %s, %s, %s)
    ''', (2, limnimeter_value, 'm', current_time)) #Capteur numéro 2, valeur généré aléatoirement, unité et la date.
    
    conn.commit()
    print(f"Pluviomètre: {pluviometer_value} L/m², Limnimètre: {limnimeter_value} m a {current_time}")

# Envoi des données toutes les secondes
try:
    while True:
        insert_fake_data()
        time.sleep(1)  
except KeyboardInterrupt:
    print("Fin")
finally:
    conn.close() # Fermeture connexion
