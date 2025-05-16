import pytest
from unittest.mock import patch, MagicMock
from api import send_sms

# Test : SMS envoyé avec succès
@patch('api.Client')
@patch('api.Connection')
def test_send_sms_success(mock_connection, mock_client_class):
    # Mock de la connexion Huawei
    mock_client = MagicMock()
    mock_client.sms.send_sms.return_value = "OK"
    mock_client_class.return_value = mock_client

    mock_conn_instance = MagicMock()
    mock_connection.return_value.__enter__.return_value = mock_conn_instance

    send_sms('+33612345678', 'Hello')
    mock_client.sms.send_sms.assert_called_once_with('+33612345678', 'Hello')

# Test : Erreur de contenu vide
@patch('api.Client')
@patch('api.Connection')
def test_send_sms_empty_message(mock_connection, mock_client_class):
    mock_client = MagicMock()
    mock_client.sms.send_sms.side_effect = Exception("Message vide")
    mock_client_class.return_value = mock_client

    mock_conn_instance = MagicMock()
    mock_connection.return_value.__enter__.return_value = mock_conn_instance

    with pytest.raises(Exception, match="Message vide"):
        send_sms('+33612345678', '')

# Test : Numéro invalide
@patch('api.Client')
@patch('api.Connection')
def test_send_sms_invalid_number(mock_connection, mock_client_class):
    mock_client = MagicMock()
    mock_client.sms.send_sms.side_effect = Exception("Numéro invalide")
    mock_client_class.return_value = mock_client

    mock_conn_instance = MagicMock()
    mock_connection.return_value.__enter__.return_value = mock_conn_instance

    with pytest.raises(Exception, match="Numéro invalide"):
        send_sms('INVALID', 'Hello')
