from huawei_lte_api.Client import Client
from huawei_lte_api.Connection import Connection

# URL de l'API HiLink
base_url = 'http://192.168.8.1'

# Créer une connexion
with Connection(base_url) as connection:
    client = Client(connection)
    def send_sms(phone, message):
        
        # Envoyer un SMSl
        sms_data = {
            'Index': '-1',
            'Phones': {'Phone': [phone]},
            'Content': message,
            'Length': len(message),
            'Reserved': '1',
            'Date': '2025-04-03 11:45:40'
        }
    
        response = client.sms.send_sms(phone,message)
        print('Réponse de l\'API:', response)
    send_sms('+33643872007','test conan')
