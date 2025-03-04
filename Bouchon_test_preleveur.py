import mysql.connector

def check_and_open_prelever(db_config):
    db = mysql.connector.connect(
        host=db_config['host'],
        user=db_config['user'],
        password=db_config['password'],
        database=db_config['database'],
        port=db_config['port']
    )
    cursor = db.cursor()
    
    # Récupérer le seuil du préleveur
    cursor.execute("SELECT seuil FROM Preleveur")
    seuil = cursor.fetchone()
    
    # Récupérer la dernière mesure du pluviomètre
    cursor.execute("SELECT valeur FROM Mesure WHERE capteur = 1 ORDER BY date DESC LIMIT 1")
    mesure_pluviometre = cursor.fetchone()
    
    # Récupérer la dernière mesure du limnimètre
    cursor.execute("SELECT valeur FROM Mesure WHERE capteur = 2 ORDER BY date DESC LIMIT 1")
    mesure_limnimetre = cursor.fetchone()
    
    db.close()

    if mesure_pluviometre and mesure_limnimetre:
        if mesure_pluviometre[0] >= 20 or mesure_limnimetre[0] > seuil[0]:   # Conditions pour comparer les mesures
            return 'Préleveur ouvert'
        else:
            return 'Préleveur fermé'

    raise ValueError("Les données nécessaires ne sont pas disponibles.")
    
# Information de connexion BDD
if __name__ == '__main__':
    db_config = {
        'host': '10.0.14.4',
        'user': 'root',
        'password': 'ieufdl',
        'database': 'eau_pure',
        'port': '9999'
    }
    result = check_and_open_prelever(db_config)
    print(result)  # Affichage du résulat (préleveur ouvert ou fermé)
