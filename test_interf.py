import pytest
from unittest.mock import patch, MagicMock
import interf

# ========================================
# Test de la fonction Envois_mesures
# ========================================

@patch('interf.mysql.connector.connect')
def test_envois_mesures(mock_connect):
    """
    Teste si Envois_mesures insère correctement une mesure dans la base de données.
    """

    # Création de mocks pour la connexion et le curseur
    mock_conn = MagicMock()
    mock_cursor = MagicMock()

    # Configuration du comportement des mocks
    mock_connect.return_value = mock_conn
    mock_conn.cursor.return_value = mock_cursor
    mock_conn.is_connected.return_value = True

    # Appel de la fonction avec des données factices
    interf.Envois_mesures(
        capteur=1,
        valeur=10.5,
        unite='L/m²',
        date='2025-04-28 10:00:00'
    )

    # Vérification des appels SQL
    mock_connect.assert_called_once()
    mock_cursor.execute.assert_called_once_with(
        '''
        INSERT INTO Mesure (capteur, valeur, unite, date)
        VALUES (%s, %s, %s, %s)
        ''',
        (1, 10.5, 'L/m²', '2025-04-28 10:00:00')
    )
    mock_conn.commit.assert_called_once()
    mock_cursor.close.assert_called_once()
    mock_conn.close.assert_called_once()



# ===============================================================
# Test de la fonction envois_mesure mais avec une mauvaise valeur
# ===============================================================


@patch('interf.mysql.connector.connect')
def test_envois_mesures_mauvaise_valeur(mock_connect):
    """
    Teste que Envois_mesures lève une exception si la valeur n'est pas convertible en float.
    """
    # Mock de la connexion
    mock_conn = MagicMock()
    mock_cursor = MagicMock()

    mock_connect.return_value = mock_conn
    mock_conn.cursor.return_value = mock_cursor
    mock_conn.is_connected.return_value = True

    with pytest.raises(Exception):
        interf.Envois_mesures(1, 'dix', 'L/m²', '2025-04-28 10:00:00')


