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

# ========================================
# Test de la fonction lire_seuils
# ========================================

@patch('interf.mysql.connector.connect')
def test_lire_seuils(mock_connect):
    """
    Teste si lire_seuils récupère correctement les seuils depuis la base.
    """

    # Création de mocks pour la connexion et le curseur
    mock_conn = MagicMock()
    mock_cursor = MagicMock()

    # Configuration du comportement
    mock_connect.return_value = mock_conn
    mock_conn.cursor.return_value = mock_cursor

    # Valeurs de seuils simulées
    mock_cursor.fetchone.side_effect = [(15.5,), (7.2,)]

    # Appel de la fonction
    db_config = {
        'host': 'localhost',
        'user': 'root',
        'password': 'root',
        'database': 'eau_pure',
        'port': 3306
    }
    seuil_pluviometre, seuil_limnimetre = interf.lire_seuils(db_config)

    # Vérification des résultats
    assert seuil_pluviometre == 15.5
    assert seuil_limnimetre == 7.2
    mock_conn.close.assert_called_once()

# ========================================
# Test de la fonction lire_mesures
# ========================================

@patch('interf.mysql.connector.connect')
def test_lire_mesures(mock_connect):
    """
    Teste si lire_mesures récupère correctement les dernières mesures depuis la base.
    """

    # Création de mocks
    mock_conn = MagicMock()
    mock_cursor = MagicMock()

    mock_connect.return_value = mock_conn
    mock_conn.cursor.return_value = mock_cursor

    # Données simulées : dernière valeur du pluviomètre et du limnimètre
    mock_cursor.fetchone.side_effect = [(12.3,), (4.5,)]

    db_config = {
        'host': 'localhost',
        'user': 'root',
        'password': 'root',
        'database': 'eau_pure',
        'port': 3306
    }
    mesure_pluviometre, mesure_limnimetre = interf.lire_mesures(db_config)

    # Vérification des résultats
    assert mesure_pluviometre == 12.3
    assert mesure_limnimetre == 4.5
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


# ===============================================
# Test de la fonction lire_seuils mauvaise valeur
# ===============================================

@patch('interf.mysql.connector.connect')
def test_lire_seuils_mauvais_resultats(mock_connect):
    """
    Teste que lire_seuils lève une exception si les valeurs récupérées ne correspondent pas à celles attendues.
    """

    # Création de mocks
    mock_conn = MagicMock()
    mock_cursor = MagicMock()

    mock_connect.return_value = mock_conn
    mock_conn.cursor.return_value = mock_cursor

    # On retourne volontairement des seuils inattendus
    mock_cursor.fetchone.side_effect = [(15.5,), (7.2,)]

    db_config = {
        'host': 'localhost',
        'user': 'root',
        'password': 'root',
        'database': 'eau_pure',
        'port': 3306
    }

    with pytest.raises(AssertionError):
        seuil_pluviometre, seuil_limnimetre = interf.lire_seuils(db_config)
        # Vérifications qui doivent échouer
        assert seuil_pluviometre == 1.5
        assert seuil_limnimetre == 0.2

# ===============================================
# Test de la fonction lire_mesure mauvaise valeur
# ===============================================
@patch('interf.mysql.connector.connect')
def test_lire_mesures_mauvaise_valeur(mock_connect):
    """
    Teste que lire_mesures lève une exception si les valeurs récupérées ne correspondent pas aux attentes.
    Ce test est conçu pour échouer volontairement mais passer car on s'attend à une erreur.
    """

    # Mocks de la connexion
    mock_conn = MagicMock()
    mock_cursor = MagicMock()

    mock_connect.return_value = mock_conn
    mock_conn.cursor.return_value = mock_cursor

    # Valeurs retournées volontairement "fausses"
    mock_cursor.fetchone.side_effect = [(12.3,), (4.5,)]

    db_config = {
        'host': 'localhost',
        'user': 'root',
        'password': 'root',
        'database': 'eau_pure',
        'port': 3306
    }

    with pytest.raises(AssertionError):
        mesure_pluviometre, mesure_limnimetre = interf.lire_mesures(db_config)

        # Ces assertions sont fausses exprès : elles doivent échouer
        assert mesure_pluviometre == 0.0
        assert mesure_limnimetre == 0.0


# ===============================================
# Test de la fonction lire_seuils valeur NONE
# ===============================================
@patch('interf.mysql.connector.connect')
def test_lire_seuils_valeurs_nulles(mock_connect):
    mock_conn = MagicMock()
    mock_cursor = MagicMock()
    mock_connect.return_value = mock_conn
    mock_conn.cursor.return_value = mock_cursor

    # Retourne des seuils NULL (None)
    mock_cursor.fetchone.side_effect = [(None,), (None,)]

    db_config = {
        'host': 'localhost',
        'user': 'root',
        'password': 'root',
        'database': 'eau_pure',
        'port': 3306
    }

    with pytest.raises(ValueError, match="pluviomètre"):
        interf.lire_seuils(db_config)

