// src/composables/useProfileNavigation.js
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'

export function useProfileNavigation() {
  const router = useRouter()
  const toast = useToast()

  /**
   * Navigue vers le profil public d'un utilisateur
   * @param {number|string} authorId - ID de l'utilisateur
   * @param {string} authorName - Nom de l'utilisateur (pour les logs/erreurs)
   */
  const goToProfile = (authorId, authorName = 'utilisateur') => {
    if (!authorId) {
      console.warn('Impossible de naviguer vers le profil : ID manquant')
      toast.add({
        severity: 'warn',
        summary: 'Navigation impossible',
        detail: 'Impossible d\'accéder au profil de cet utilisateur',
        life: 3000
      })
      return
    }

    try {
      const userId = parseInt(authorId)
      if (isNaN(userId) || userId <= 0) {
        throw new Error('ID utilisateur invalide')
      }

      router.push(`/profile/${userId}`)
    } catch (error) {
      console.error('Erreur navigation profil:', error)
      toast.add({
        severity: 'error',
        summary: 'Erreur de navigation',
        detail: `Impossible d'accéder au profil de ${authorName}`,
        life: 3000
      })
    }
  }

  /**
   * Vérifie si la navigation vers un profil est possible
   * @param {number|string} authorId - ID de l'utilisateur
   * @returns {boolean}
   */
  const canNavigateToProfile = (authorId) => {
    return authorId && !isNaN(parseInt(authorId)) && parseInt(authorId) > 0
  }

  /**
   * Génère le titre pour l'attribut title des éléments cliquables
   * @param {string} authorName - Nom de l'utilisateur
   * @returns {string}
   */
  const getProfileTooltip = (authorName) => {
    return `Voir le profil de ${authorName}`
  }

  return {
    goToProfile,
    canNavigateToProfile,
    getProfileTooltip
  }
}