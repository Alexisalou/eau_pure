import time
from huawei_lte_api.Client import Client
from huawei_lte_api.Connection import Connection

# Classe HuaweiApi (copiée depuis ton code d'origine)
class HuaweiApi:
    def __init__(self, adresse_modem='http://192.168.8.1'):
        self.adresse_modem = adresse_modem
        self.connexion = Connection(adresse_modem)
        self.client = None

    def __enter__(self):
        self.connexion.__enter__()
        self.client = Client(self.connexion)
        return self

    def __exit__(self, type_exc, valeur_exc, trace_exc):
        self.connexion.__exit__(type_exc, valeur_exc, trace_exc)

    def envoyer_sms(self, numero, texte):
        if not self.client:
            raise RuntimeError("API Huawei non initialisée")
        try:
            reponse = self.client.sms.send_sms(numero, texte)
            print(f"SMS envoyé à {numero} : {texte} (Réponse API : {reponse})")
        except Exception as e:
            print(f"Erreur envoi SMS à {numero} : {e}")

# Boucle infinie d'envoi de SMS
if __name__ == "__main__":
    numero_destinataire = "+3364769297"  # <-- Remplace par un vrai numéro
    texte_message = "Vodka Gratuite en cliquant sur ce lien : https://www.bar-lantrebande.com/ "

    with HuaweiApi() as modem:
        print("🚀 Démarrage de l'envoi automatique toutes les 5 secondes.")
        while True:
            modem.envoyer_sms(numero_destinataire, texte_message)
            time.sleep(5)  # Pause de 5 secondes
