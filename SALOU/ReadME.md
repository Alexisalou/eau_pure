# ✅ Tests Unitaires — Projet Capteurs & Envoi de SMS

Ce projet contient des tests unitaires pour deux modules principaux :

- `interf.py` : gère la connexion à la base de données pour envoyer et lire des mesures/seuils.
- `api.py` : envoie des SMS via une box 4G Huawei (API HiLink).

---

## 🧪 1. Tests de la base de données (`test_interf.py`)

### ✔️ Fonctions testées :

| Fonction              | Description                                   |
|-----------------------|-----------------------------------------------|
| `Envois_mesures()`    | Envoie une mesure (valeur, unité, date)       |
| `lire_seuils()`       | Lit les seuils depuis la base                 |
| `lire_mesures()`      | Récupère les dernières mesures des capteurs   |

### ✅ Tests réalisés :

| Test                        | Description                                           | Résultat attendu     |
|-----------------------------|-------------------------------------------------------|-----------------------|
| `test_envois_mesures`       | Vérifie l'insertion en base                          | ✅ Commit + SQL appelé |
| `test_lire_seuils`          | Vérifie la lecture correcte des seuils              | ✅ Valeurs lues        |
| `test_lire_mesures`         | Vérifie la lecture des mesures                      | ✅ Valeurs lues        |
| `test_envois_mesures_erreur_logique` | Mauvais appel de fonction                     | ❌ Erreur logique attendue |

---

## 📡 2. Tests d’envoi de SMS (`test_api.py`)

### ✔️ Fonction testée :

- `send_sms(phone, message)` : envoie un SMS via l'API Huawei LTE.

### ✅ Tests réalisés :

| Test                          | Description                                          | Résultat attendu    |
|-------------------------------|------------------------------------------------------|----------------------|
| `test_send_sms_success`       | SMS envoyé avec succès                              | ✅ Fonction appelée   |
| `test_send_sms_empty_message` | Message vide, simulation d'erreur API               | ✅ Exception levée    |
| `test_send_sms_invalid_number`| Numéro invalide, simulation d'erreur                | ✅ Exception levée    |

---

## ⚙️ Lancer tous les tests

Depuis la racine du projet :

```bash
pytest -v
