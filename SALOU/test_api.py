import pytest
from unittest.mock import patch, MagicMock
import builtins

# On simule ici la structure du fichier api.py
# Le but est de tester la fonction `send_sms` sans avoir besoin de la box 4G

# ✅ Test d'envoi réussi
@patch('huawei_lte_api.Connection')
@patch('huawei_lte_api.Client')
def test_send_sms_success(mock_client_class, mock_connection_class):
    mock_connection_instance = MagicMock()
    mock_client_instance = MagicMock()
    
    mock_connection_class.return_value.__enter__.return_value = mock_connection_instance
    mock_client_class.return_value = mock_client_instance

    # Appel simulé à l'envoi de SMS
    from api import send_sms
    send_sms('+33612345678', 'Test réussi')

    mock_client_instance.sms.send_sms.assert_called_once_with('+33612345678', 'Test réussi')

# ❌ Test avec un message vide
@patch('huawei_lte_api.Connection')
@patch('huawei_lte_api.Client')
def test_send_sms_message_vide(mock_client_class, mock_connection_class):
    mock_client_instance = MagicMock()
    mock_connection_class.return_value.__enter__.return_value = MagicMock()
    mock_client_class.return_value = mock_client_instance

    from api import send_sms

    with pytest.raises(Exception):
        send_sms('+33612345678', '')  # Message vide non géré dans api.py

# ❌ Test avec un numéro invalide
@patch('huawei_lte_api.Connection')
@patch('huawei_lte_api.Client')
def test_send_sms_numero_invalide(mock_client_class, mock_connection_class):
    mock_client_instance = MagicMock()
    mock_client_instance.sms.send_sms.side_effect = Exception("Numéro invalide")

    mock_connection_class.return_value.__enter__.return_value = MagicMock()
    mock_client_class.return_value = mock_client_instance

    from api import send_sms

    with pytest.raises(Exception):
        send_sms('1234', 'Test avec numéro invalide')
