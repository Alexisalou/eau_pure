# 🌦️ Projet de Surveillance Pluviométrique et Limnimétrique

Ce projet utilise des capteurs connectés à un Raspberry Pi pour mesurer :
- Les précipitations (pluviomètre)
- Le niveau d’eau (limnimètre)
Les mesures sont stockées dans une base de données MySQL, et des alertes peuvent être envoyées par SMS via une box 4G Huawei.

---

## 🧪 Tests unitaires

Les tests sont répartis dans deux fichiers :

- `test_interf.py` → tests des fonctions de base de données
- `test_api.py` → tests des fonctions d'envoi de SMS

---

## 💾 Tests pour la base de données (`interf.py`)

Le fichier `interf.py` contient les fonctions :
- `Envois_mesures()` : insère une nouvelle mesure dans la base
- `lire_seuils()` : lit les seuils des capteurs dans la table `Preleveur`
- `lire_mesures()` : récupère les dernières mesures de chaque capteur

---

### ✅ Tests réalisés dans `test_interf.py`

| Test                          | Description                                                             | Attendu     |
|-------------------------------|-------------------------------------------------------------------------|-------------|
| `test_envois_mesures`         | Teste l’insertion d’une mesure via une base simulée (`mock`)           | ✅ Réussi   |
| `test_lire_seuils`            | Récupère les seuils de la table `Preleveur`                             | ✅ Réussi   |
| `test_lire_mesures`           | Lit les dernières mesures du pluviomètre et limnimètre                  | ✅ Réussi   |
| `test_lire_seuils_erreur`     | 💥 Simule un appel de fonction incorrect (sans paramètre)              | ❌ Échoue   |

---

## 📡 Tests pour l'envoi de SMS (`api.py`)

Le fichier `api.py` contient une fonction `send_sms(phone, message)` qui utilise l'API HiLink (Huawei) pour envoyer des SMS via une box 4G.

---

### ✅ Tests réalisés dans `test_api.py`

| Test                              | Description                                                  | Attendu      |
|-----------------------------------|--------------------------------------------------------------|--------------|
| `test_send_sms_success`           | Envoi d'un SMS valide                                        | ✅ Réussi    |
| `test_send_sms_message_vide`      | Message vide (génère une erreur API)                         | ❌ Échoue    |
| `test_send_sms_numero_invalide`   | Numéro mal formé (non reconnu par la box)                    | ❌ Échoue    |

---

## ▶️ Lancer les tests

Exécute les tests avec `pytest` :

```bash
# Tous les tests
pytest

# Tests spécifiques
pytest -v test_interf.py
pytest -v test_api.py
