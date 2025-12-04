# Panel d'Administration VroomVroom 🚗

## Installation et Configuration

### 1. Mise à jour de la base de données

Accédez à `http://localhost/covoiturage/setup_admin.php` dans votre navigateur.
Ce script va automatiquement ajouter les colonnes nécessaires :
- `role` (VARCHAR) : pour définir le rôle (user/admin)
- `status` (VARCHAR) : pour gérer le statut du compte (active/blocked)

### 2. Créer un compte administrateur

**Option 1 : Via l'interface web**
1. Créez un compte utilisateur normal via `inscription.html`
2. Utilisez phpMyAdmin ou un client MySQL pour exécuter :
```sql
UPDATE users SET role = 'admin' WHERE email = 'votre-email@example.com';
```

**Option 2 : Via la ligne de commande**
```bash
cd C:\xampp\mysql\bin
mysql.exe -u root covoiturage
```
```sql
UPDATE users SET role = 'admin' WHERE email = 'votre-email@example.com';
```

### 3. Connexion Admin

- Accédez à `authentification.php`
- Connectez-vous avec les identifiants du compte admin
- Vous serez automatiquement redirigé vers le Dashboard Admin

## Fonctionnalités du Panel Admin

### 📊 Dashboard (`admin_dashboard.php`)
- **Statistiques globales** :
  - Nombre total d'utilisateurs
  - Nombre total de trajets
  - Nombre total de réservations
  - Revenu total généré
- **Activité récente** :
  - 5 derniers trajets publiés
  - 5 dernières réservations

### 👥 Gestion des Utilisateurs (`admin_users.php`)
- **Voir tous les utilisateurs** avec :
  - Informations personnelles (nom, email, téléphone, ville)
  - Nombre de trajets publiés
  - Nombre de réservations effectuées
  - Statut du compte (Active/Blocked)
- **Actions disponibles** :
  - **Bloquer** : Empêche un utilisateur de se connecter
  - **Débloquer** : Réactive un compte bloqué
  - **Supprimer** : Supprime l'utilisateur et toutes ses données (trajets et réservations)

### 🚗 Gestion des Trajets (`admin_journeys.php`)
- **Voir tous les trajets** avec :
  - Informations du conducteur
  - Villes de départ et d'arrivée
  - Date et heure
  - Nombre de places disponibles
  - Prix par place
  - Nombre de réservations
- **Actions disponibles** :
  - **Supprimer** : Supprime le trajet et toutes les réservations associées

### 🎫 Gestion des Réservations (`admin_bookings.php`)
- **Voir toutes les réservations** avec :
  - Informations du passager (nom, email, téléphone)
  - Détails du trajet
  - Informations du conducteur
  - Nombre de places réservées
  - Prix total
  - Date de réservation
- **Actions disponibles** :
  - **Supprimer** : Annule une réservation

## Sécurité

### Protection des pages admin
Toutes les pages admin vérifient :
1. Si l'utilisateur est connecté (`$_SESSION['user_cin']`)
2. Si l'utilisateur a le rôle `admin` (`$_SESSION['user_role'] === 'admin'`)

Si ces conditions ne sont pas remplies, l'utilisateur est redirigé vers la page de connexion.

### Blocage de compte
Quand un compte est bloqué (`status = 'blocked'`) :
- L'utilisateur ne peut plus se connecter
- Un message d'erreur s'affiche : "Your account has been blocked. Please contact administrator."

## Navigation

Le panel admin dispose d'une **sidebar de navigation** présente sur toutes les pages :
- 📊 Dashboard
- 👥 Users Management
- 🚗 Journeys Management
- 🎫 Bookings Management

## Structure des fichiers

```
covoiturage/
├── admin_dashboard.php    # Tableau de bord principal
├── admin_users.php        # Gestion des utilisateurs
├── admin_journeys.php     # Gestion des trajets
├── admin_bookings.php     # Gestion des réservations
├── setup_admin.php        # Script d'installation (à exécuter une fois)
├── check_db.php           # Vérification de la structure DB
└── authentification.php   # Authentification avec support admin
```

## Base de données

### Table `users` - Colonnes ajoutées
```sql
role VARCHAR(20) DEFAULT 'user'    -- 'user' ou 'admin'
status VARCHAR(20) DEFAULT 'active' -- 'active' ou 'blocked'
```

### Cascade de suppression
Lors de la suppression d'un utilisateur :
1. Suppression de toutes ses réservations (`booking`)
2. Suppression de tous ses trajets (`journey`)
3. Suppression de l'utilisateur (`users`)

Lors de la suppression d'un trajet :
1. Suppression de toutes les réservations liées (`booking`)
2. Suppression du trajet (`journey`)

## Dépannage

### Les colonnes `role` et `status` n'existent pas
→ Accédez à `http://localhost/covoiturage/setup_admin.php`

### Je ne peux pas me connecter en tant qu'admin
→ Vérifiez que votre compte a bien `role = 'admin'` dans la table `users`

### Erreur "Database connection failed"
→ Vérifiez que XAMPP (Apache + MySQL) est démarré

### Je suis redirigé vers `authentification.php`
→ Votre compte n'a pas le rôle `admin` ou vous n'êtes pas connecté

## Améliorations futures possibles

- 🔍 Filtrage et recherche dans les listes
- 📄 Pagination pour les grandes listes
- 📧 Notifications par email aux utilisateurs bloqués
- 📊 Graphiques d'évolution (Chart.js)
- 📝 Logs d'activité admin
- 🔐 Gestion des permissions granulaires
- 💬 Système de messages/support
- 📱 Version responsive mobile optimisée

## Support

Pour toute question ou problème, contactez votre administrateur système.
