import pytest
from unittest.mock import patch, MagicMock
import interf_GSM

# ----------- Test lire_seuils -----------
@patch('interf_GSM.mysql.connector.connect')
def test_lire_seuils_valeurs_valides(mock_connect):
    mock_db = MagicMock()
    mock_cursor = MagicMock()
    mock_connect.return_value = mock_db
    mock_db.cursor.return_value = mock_cursor
    mock_cursor.fetchone.return_value = (50.0, 10.0)

    db_config = {
        'host': 'localhost',
        'user': 'root',
        'password': 'root',
        'database': 'eau_pure',
        'port': 3306
    }

    seuils = interf_GSM.lire_seuils(db_config)
    assert seuils == (50.0, 10.0)


# ----------- Test HuaweiApi -----------
@patch('interf_GSM.Client')
@patch('interf_GSM.Connection')
def test_huaweiapi_context(mock_conn, mock_client):
    mock_conn_instance = MagicMock()
    mock_client_instance = MagicMock()
    mock_conn.return_value = mock_conn_instance
    mock_client.return_value = mock_client_instance

    with interf_GSM.HuaweiApi() as api:
        assert api.client == mock_client_instance
        mock_conn.assert_called_once()
        mock_client.assert_called_once_with(mock_conn_instance)



@patch('interf_GSM.Client')
@patch('interf_GSM.Connection')
def test_huaweiapi_send_sms(mock_conn, mock_client):
    mock_conn_instance = MagicMock()
    mock_client_instance = MagicMock()
    mock_client_instance.sms.send_sms = MagicMock(return_value="OK")

    mock_conn.return_value.__enter__.return_value = mock_conn_instance
    mock_client.return_value = mock_client_instance

    with interf_GSM.HuaweiApi() as api:
        api.client = mock_client_instance
        api.send_sms("+33612345678", "Message test")

        mock_client_instance.sms.send_sms.assert_called_once_with("+33612345678", "Message test")


# ----------- Test verifier_et_alert -----------
@patch('interf_GSM.lire_seuils')
@patch('interf_GSM.mysql.connector.connect')
def test_verifier_et_alert_alerte_envoyee(mock_connect, mock_lire_seuils):
    mock_lire_seuils.return_value = (50.0, 10.0)

    mock_db = MagicMock()
    mock_cursor = MagicMock()
    mock_connect.return_value = mock_db
    mock_db.cursor.return_value = mock_cursor

    mock_cursor.fetchall.return_value = [
        {
            'technicien_id': 1,
            'numero_de_telephone': '+33612345678',
            'station_id': 42,
            'reference': 'PLUVIOMETRE',
            'valeur': 55.0
        },
        {
            'technicien_id': 1,
            'numero_de_telephone': '+33612345678',
            'station_id': 42,
            'reference': 'LIMNIMETRE',
            'valeur': 5.0
        }
    ]

    mock_api = MagicMock()
    db_config = {
        'host': 'localhost',
        'user': 'root',
        'password': 'root',
        'database': 'eau_pure',
        'port': 3306
    }

    interf_GSM.verifier_et_alert(db_config, mock_api)

    mock_api.send_sms.assert_called_once()
    args, kwargs = mock_api.send_sms.call_args
    assert "+33612345678" in args[0]
    assert "PLUVIOMETRE" in args[1]


@patch('interf_GSM.lire_seuils')
@patch('interf_GSM.mysql.connector.connect')
def test_verifier_et_alert_aucune_alerte(mock_connect, mock_lire_seuils):
    mock_lire_seuils.return_value = (60.0, 10.0)

    mock_db = MagicMock()
    mock_cursor = MagicMock()
    mock_connect.return_value = mock_db
    mock_db.cursor.return_value = mock_cursor

    mock_cursor.fetchall.return_value = [
        {
            'technicien_id': 1,
            'numero_de_telephone': '+33612345678',
            'station_id': 42,
            'reference': 'PLUVIOMETRE',
            'valeur': 50.0
        },
        {
            'technicien_id': 1,
            'numero_de_telephone': '+33612345678',
            'station_id': 42,
            'reference': 'LIMNIMETRE',
            'valeur': 8.0
        }
    ]

    mock_api = MagicMock()
    db_config = {
        'host': 'localhost',
        'user': 'root',
        'password': 'root',
        'database': 'eau_pure',
        'port': 3306
    }

    interf_GSM.verifier_et_alert(db_config, mock_api)

    mock_api.send_sms.assert_not_called()
