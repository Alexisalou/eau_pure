import pytest
from unittest.mock import patch, MagicMock
import api

# ✅ Test 1 : simulation d'un envoi réussi
@patch('api.Connection')
@patch('api.Client')
def test_send_sms_success(mock_client_class, mock_connection_class):
    mock_connection = MagicMock()
    mock_connection_class.return_value.__enter__.return_value = mock_connection

    mock_client = MagicMock()
    mock_client_class.return_value = mock_client
    mock_client.sms.send_sms.return_value = {'response': 'OK'}

    response = api.send_sms('+33600000000', 'Message test')
    
    # Vérifications
    mock_client.sms.send_sms.assert_called_once_with('+33600000000', 'Message test')
    assert response == {'response': 'OK'}

# ❌ Test 2 : simulation d'une erreur (message vide)
@patch('api.Connection')
@patch('api.Client')
def test_send_sms_message_vide(mock_client_class, mock_connection_class):
    mock_connection = MagicMock()
    mock_connection_class.return_value.__enter__.return_value = mock_connection

    mock_client = MagicMock()
    mock_client_class.return_value = mock_client

    # Simule une exception levée par l'API Huawei si le message est vide
    mock_client.sms.send_sms.side_effect = Exception("Message vide interdit")

    with pytest.raises(Exception) as excinfo:
        api.send_sms('+33600000000', '')

    assert "Message vide interdit" in str(excinfo.value)

# ❌ Test 3 : numéro mal formé
@patch('api.Connection')
@patch('api.Client')
def test_send_sms_numero_invalide(mock_client_class, mock_connection_class):
    mock_connection = MagicMock()
    mock_connection_class.return_value.__enter__.return_value = mock_connection

    mock_client = MagicMock()
    mock_client_class.return_value = mock_client

    # Simule une exception si le numéro est invalide
    mock_client.sms.send_sms.side_effect = Exception("Numéro invalide")

    with pytest.raises(Exception) as excinfo:
        api.send_sms('123abc', 'Test')

    assert "Numéro invalide" in str(excinfo.value)
