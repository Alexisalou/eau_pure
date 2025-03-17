import time
from datetime import datetime
from interf import generate_fake_data, Envois_mesures, lire_seuils, lire_mesures

# Information de connexion à la BDD
DATABASE_HOST = '10.0.14.4'  
DATABASE_NAME = 'eau_pure'  
DATABASE_USER = 'root'  
DATABASE_PASSWORD = 'ieufdl'
DATABASE_PORT = '9999'  # Port mysql ouvert sur le serveur

# Fonction pour insérer les données factices dans la BDD
def insert_fake_data():
    pluviometer_value, limnimeter_value = generate_fake_data()
    current_time = datetime.now().strftime('%Y-%m-%d %H:%M:%S')  
    
    # Insertion des données du pluviomètre
    Envois_mesures(1, pluviometer_value, 'L/m²', current_time)
    
    # Insertion des données du limnimètre
    Envois_mesures(2, limnimeter_value, 'm', current_time)
    
    print(f"Pluviomètre: {pluviometer_value} L/m², Limnimètre: {limnimeter_value} m à {current_time}")

# Envoi des données toutes les secondes et test des seuils
try:
    while True:
        insert_fake_data()
        
        db_config = {
            'host': DATABASE_HOST,
            'user': DATABASE_USER,
            'password': DATABASE_PASSWORD,
            'database': DATABASE_NAME,
            'port': DATABASE_PORT
        }
        
        try:
            # Lire les seuils
            seuil_pluviometre, seuil_limnimetre = lire_seuils(db_config)
            print(f"Seuil Pluviomètre: {seuil_pluviometre}, Seuil Limnimètre: {seuil_limnimetre}")
            
            # Lire les mesures
            mesure_pluviometre, mesure_limnimetre = lire_mesures(db_config)
            print(f"Mesure Pluviomètre: {mesure_pluviometre}, Mesure Limnimètre: {mesure_limnimetre}")
            
            # Vérifier les conditions pour ouvrir le préleveur
            if mesure_pluviometre >= seuil_pluviometre or mesure_limnimetre > seuil_limnimetre:
                print('Préleveur ouvert')
            else:
                print('Préleveur fermé')
        
        except ValueError as e:
            print(e)
        
        time.sleep(1)  
except KeyboardInterrupt:
    print("Fin")
finally:
    # Fermeture de la connexion
    pass
