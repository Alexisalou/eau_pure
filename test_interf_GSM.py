import pytest
from unittest.mock import MagicMock, patch
from interf_GSM import verifier_et_alerter

# Configuration de test
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': 'root',
    'database': 'eau_pure',
    'port': 3306
}

@patch('interf_GSM.mysql.connector.connect')
def test_verifier_et_alert_alerte_envoyee(mock_connect):
    # Mock DB
    mock_conn = MagicMock()
    mock_cursor = MagicMock()
    mock_connect.return_value = mock_conn
    mock_conn.cursor.return_value = mock_cursor
    
    # Pluviomètre dépasse le seuil (60 > 50*100)
    mock_cursor.fetchall.return_value = [{
        'technicien_id': 1,
        'numero_de_telephone': '0612345678',
        'station_id': 1,
        'reference': 'PLUVIOMETRE',
        'valeur': 6000.0,  # dépasse 50*100 = 5000
        'seuil_pluviometre': 50.0,
        'seuil_limnimetre': 10.0
    }]
    
    modem_mock = MagicMock()
    
    verifier_et_alerter(db_config, modem_mock)
    
    # SMS doit être envoyé
    modem_mock.envoyer_sms.assert_called_once()
    args, _ = modem_mock.envoyer_sms.call_args
    assert 'ALERTE DE SEUIL' in args[1]

@patch('interf_GSM.mysql.connector.connect')
def test_verifier_et_alert_aucune_alerte(mock_connect):
    mock_conn = MagicMock()
    mock_cursor = MagicMock()
    mock_connect.return_value = mock_conn
    mock_conn.cursor.return_value = mock_cursor

    # Valeur en dessous des seuils
    mock_cursor.fetchall.return_value = [{
        'technicien_id': 1,
        'numero_de_telephone': '0612345678',
        'station_id': 1,
        'reference': 'LIMNIMETRE',
        'valeur': 5.0,
        'seuil_pluviometre': 50.0,
        'seuil_limnimetre': 10.0
    }]

    modem_mock = MagicMock()
    verifier_et_alerter(db_config, modem_mock)
    modem_mock.envoyer_sms.assert_not_called()

@patch('interf_GSM.mysql.connector.connect', side_effect=Exception("Erreur DB"))
def test_verifier_et_alert_erreur_connexion(mock_connect):
    modem_mock = MagicMock()
    verifier_et_alerter(db_config, modem_mock)
    modem_mock.envoyer_sms.assert_not_called()
@patch('interf_GSM.mysql.connector.connect')
def test_verifier_et_alert_seuil_manquant(mock_connect):
    mock_conn = MagicMock()
    mock_cursor = MagicMock()
    mock_connect.return_value = mock_conn
    mock_conn.cursor.return_value = mock_cursor

    # seuil_pluviometre manquant (None)
    mock_cursor.fetchall.return_value = [{
        'technicien_id': 1,
        'numero_de_telephone': '0612345678',
        'station_id': 1,
        'reference': 'PLUVIOMETRE',
        'valeur': 6000.0,
        'seuil_pluviometre': None,
        'seuil_limnimetre': 10.0
    }]

    modem_mock = MagicMock()
    verifier_et_alerter(db_config, modem_mock)
    modem_mock.envoyer_sms.assert_not_called()

@patch('interf_GSM.mysql.connector.connect')
def test_verifier_et_alert_sms_exception(mock_connect):
    mock_conn = MagicMock()
    mock_cursor = MagicMock()
    mock_connect.return_value = mock_conn
    mock_conn.cursor.return_value = mock_cursor

    # Valeur dépasse le seuil => SMS devrait être envoyé
    mock_cursor.fetchall.return_value = [{
        'technicien_id': 1,
        'numero_de_telephone': '0612345678',
        'station_id': 1,
        'reference': 'LIMNIMETRE',
        'valeur': 20.0,
        'seuil_pluviometre': 50.0,
        'seuil_limnimetre': 10.0
    }]

    modem_mock = MagicMock()
    # Simule une erreur lors de l'envoi de SMS
    modem_mock.envoyer_sms.side_effect = Exception("Erreur d'envoi")

    try:
        verifier_et_alerter(db_config, modem_mock)
    except Exception:
        pytest.fail("La fonction ne doit pas lever une exception même si le SMS échoue")
