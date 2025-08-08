export const authConfigs = {
  decks: {
    icon: 'pi pi-clone',
    title: 'Accès aux Decks',
    description: `
      Découvrez les decks de la communauté et suivez le metagame en temps réel.
      <br>
      <strong>Connectez-vous pour accéder à toutes les fonctionnalités !</strong>
    `,
    features: [
      { icon: 'pi pi-users', label: 'Decks Communautaires' },
      { icon: 'pi pi-chart-line', label: 'Metagame' }
    ]
  },

  forums: {
    icon: 'pi pi-comments',
    title: 'Accès aux Discussions',
    description: `
      Participez aux discussions avec la communauté TCG, partagez vos stratégies et découvrez les dernières actualités.
      <br>
      <strong>Connectez-vous pour rejoindre les conversations !</strong>
    `,
    features: [
      { icon: 'pi pi-comment', label: 'Créer des sujets' },
      { icon: 'pi pi-reply', label: 'Répondre aux posts' },
      { icon: 'pi pi-thumbs-up', label: 'Voter et réagir' }
    ]
  },

  myDecks: {
    icon: 'pi pi-user',
    title: 'Mes Decks Personnels',
    description: `
      Créez, modifiez et gérez vos decks personnels. Sauvegardez vos créations et partagez-les avec la communauté.
      <br>
      <strong>Connectez-vous pour accéder à votre collection !</strong>
    `,
    features: [
      { icon: 'pi pi-plus', label: 'Créer des decks' },
      { icon: 'pi pi-pencil', label: 'Modifier vos decks' },
      { icon: 'pi pi-share-alt', label: 'Partager avec la communauté' }
    ]
  },

  events: {
    icon: 'pi pi-calendar',
    title: 'Accès aux Événements',
    description: `
      Découvrez les événements TCG près de chez vous, participez aux tournois et organisez vos propres événements.
      <br>
      <strong>Connectez-vous pour participer à la vie communautaire !</strong>
    `,
    features: [
      { icon: 'pi pi-map-marker', label: 'Événements locaux' },
      { icon: 'pi pi-trophy', label: 'Tournois compétitifs' },
      { icon: 'pi pi-calendar-plus', label: 'Créer des événements' }
    ]
  },

  myEvents: {
    icon: 'pi pi-calendar-plus',
    title: 'Mes Événements',
    description: `
      Gérez vos événements créés, suivez vos inscriptions et organisez votre calendrier TCG personnel.
      <br>
      <strong>Connectez-vous pour accéder à votre agenda !</strong>
    `,
    features: [
      { icon: 'pi pi-calendar', label: 'Mes inscriptions' },
      { icon: 'pi pi-cog', label: 'Gérer mes événements' },
      { icon: 'pi pi-users', label: 'Suivi des participants' }
    ]
  },

  profile: {
    icon: 'pi pi-user-edit',
    title: 'Accès au Profil',
    description: `
      Gérez votre profil, vos préférences et consultez vos statistiques de jeu.
      <br>
      <strong>Connectez-vous pour personnaliser votre expérience !</strong>
    `,
    features: [
      { icon: 'pi pi-cog', label: 'Paramètres du compte' },
      { icon: 'pi pi-chart-bar', label: 'Statistiques de jeu' },
      { icon: 'pi pi-bell', label: 'Notifications' }
    ]
  }
}