import pytest
from unittest.mock import patch, MagicMock
import interf 

# Test de Envois_mesures
@patch('interf.mysql.connector.connect')
def test_envois_mesures(mock_connect):
    # Simuler la connexion
    mock_conn = MagicMock()
    mock_cursor = MagicMock()
    mock_connect.return_value = mock_conn
    mock_conn.cursor.return_value = mock_cursor
    mock_conn.is_connected.return_value = True

    # Appeler la fonction
    interf.Envois_mesures(1, 10.5, 'L/m²', '2025-04-28 10:00:00')

    # Vérifier que connect a été appelée
    mock_connect.assert_called_once()

    # Vérifier que execute a été appelée avec la bonne requête
    mock_cursor.execute.assert_called_once_with('''
        INSERT INTO Mesure (capteur, valeur, unite, date)
        VALUES (%s, %s, %s, %s)
        ''', (1, 10.5, 'L/m²', '2025-04-28 10:00:00'))

    # Vérifier que commit a été appelée
    mock_conn.commit.assert_called_once()

    # Vérifier que close a été appelée
    mock_cursor.close.assert_called_once()
    mock_conn.close.assert_called_once()

# Test de lire_seuils
@patch('interf.mysql.connector.connect')
def test_lire_seuils(mock_connect):
    # Préparer un mock de retour
    mock_conn = MagicMock()
    mock_cursor = MagicMock()
    mock_connect.return_value = mock_conn
    mock_conn.cursor.return_value = mock_cursor

    # Simuler fetchone()
    mock_cursor.fetchone.side_effect = [(15.5,), (7.2,)]

    # Appeler la fonction
    db_config = {
        'host': 'localhost',
        'user': 'root',
        'password': 'root',
        'database': 'eau_pure',
        'port': 3306
    }
    seuil_pluviometre, seuil_limnimetre = interf.lire_seuils(db_config)

    # Vérifier les valeurs récupérées
    assert seuil_pluviometre == 15.5
    assert seuil_limnimetre == 7.2

    # Vérifier que close a été appelée
    mock_conn.close.assert_called_once()

# Test de lire_mesures
@patch('interf.mysql.connector.connect')
def test_lire_mesures(mock_connect):
    # Préparer un mock de retour
    mock_conn = MagicMock()
    mock_cursor = MagicMock()
    mock_connect.return_value = mock_conn
    mock_conn.cursor.return_value = mock_cursor

    # Simuler fetchone() pour deux capteurs
    mock_cursor.fetchone.side_effect = [(12.3,), (4.5,)]

    # Appeler la fonction
    db_config = {
        'host': 'localhost',
        'user': 'root',
        'password': 'root',
        'database': 'eau_pure',
        'port': 3306
    }
    mesure_pluviometre, mesure_limnimetre = interf.lire_mesures(db_config)

    # Vérifier les valeurs récupérées
    assert mesure_pluviometre == 12.3
    assert mesure_limnimetre == 4.5

    # Vérifier que close a été appelée
    mock_conn.close.assert_called_once()
