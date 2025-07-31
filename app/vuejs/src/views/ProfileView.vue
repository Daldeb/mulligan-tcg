<template>
  <div class="profile-page is-profile-view">
    <div class="container">
      <div class="profile-grid">
        
        <!-- Colonne principale (2/3) -->
        <div class="main-profile">
          
          <!-- En-tête profil -->
          <section class="profile-header slide-in-down">
            <Card class="gaming-card profile-header-card">
              <template #content>
                <div class="profile-header-content">
                  <!-- Avatar et info de base -->
                  <div class="avatar-section">
                    <div class="avatar-container">
                      <img 
                        v-if="!avatarPreview && user.avatar"
                        :src="`${backendUrl}/uploads/${user.avatar}`"
                        class="profile-avatar avatar-image"
                        alt="Avatar"
                        @error="console.log('Image failed to load:', `${backendUrl}/uploads/${user.avatar}`)"
                        @load="console.log('Image loaded successfully:', `${backendUrl}/uploads/${user.avatar}`)"
                      />
                      <Avatar 
                        v-else-if="!avatarPreview && !user.avatar"
                        :label="user.pseudo?.charAt(0).toUpperCase() ?? 'U'"
                        size="xlarge"
                        shape="circle"
                        class="profile-avatar"
                      />
                      <img 
                        v-else
                        :src="avatarPreview"
                        class="profile-avatar avatar-preview"
                        alt="Prévisualisation avatar"
                      />
                      <button class="avatar-edit-btn" @click="openAvatarEditor" :disabled="isLoading">
                        <i class="pi pi-camera"></i>
                      </button>
                    </div>
                    
                    <div class="basic-info">
                      <div class="username-section">
                        <h1 class="username">{{ user.pseudo }}</h1>
                        <div class="role-badges">
                          <span :class="['role-badge', userRole]">
                            <i :class="getRoleIcon(userRole)"></i>
                            {{ getRoleLabel(userRole) }}
                          </span>
                          <span v-if="user.isVerified" class="verified-badge">
                            <i class="pi pi-verified"></i>
                            Vérifié
                          </span>
                        </div>
                      </div>
                      
                      <div class="profile-stats">
                        <div class="stat-item">
                          <span class="stat-value">{{ user.stats?.topics || 0 }}</span>
                          <span class="stat-label">Topics</span>
                        </div>
                        <div class="stat-item">
                          <span class="stat-value">{{ user.stats?.replies || 0 }}</span>
                          <span class="stat-label">Réponses</span>
                        </div>
                        <div class="stat-item">
                          <span class="stat-value">{{ user.stats?.events || 0 }}</span>
                          <span class="stat-label">Événements</span>
                        </div>
                        <div class="stat-item">
                          <span class="stat-value">{{ user.stats?.reputation || 0 }}</span>
                          <span class="stat-label">Réputation</span>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!-- Actions rapides -->
                  <div class="quick-actions">
                    <Button 
                      icon="pi pi-pencil"
                      label="Modifier profil"
                      class="emerald-outline-btn"
                      @click="editMode = !editMode"
                    />
                  </div>
                </div>
              </template>
            </Card>
          </section>

          <!-- Section d'édition (si activée) -->
          <section v-if="editMode" class="edit-section slide-in-up">
            <Card class="gaming-card edit-card">
              <template #header>
                <div class="card-header-custom edit-header">
                  <i class="pi pi-user-edit header-icon"></i>
                  <h3 class="header-title">Modifier mon profil</h3>
                </div>
              </template>
              <template #content>
                <form @submit.prevent="saveProfile" class="edit-form">
                  <div class="form-grid">
                    <div class="field-group">
                      <label for="pseudo" class="field-label">Pseudo</label>
                      <InputText 
                        id="pseudo"
                        v-model="editForm.pseudo" 
                        class="emerald-input"
                        :class="{ 'error': !!editErrors.pseudo }"
                      />
                      <small v-if="editErrors.pseudo" class="field-error">{{ editErrors.pseudo }}</small>
                    </div>
                    
                    <div class="field-group">
                      <label for="favoriteClass" class="field-label">Classe favorite</label>
                      <InputText 
                        id="favoriteClass"
                        v-model="editForm.favoriteClass" 
                        placeholder="ex: Mage, Guerrier..."
                        class="emerald-input"
                      />
                    </div>
                  </div>
                  
                  <div class="form-grid">
                    <div class="field-group">
                      <label for="firstName" class="field-label">Prénom</label>
                      <InputText 
                        id="firstName"
                        v-model="editForm.firstName" 
                        class="emerald-input"
                        :class="{ 'error': !!editErrors.firstName }"
                      />
                      <small v-if="editErrors.firstName" class="field-error">{{ editErrors.firstName }}</small>
                    </div>
                    
                    <div class="field-group">
                      <label for="lastName" class="field-label">Nom</label>
                      <InputText 
                        id="lastName"
                        v-model="editForm.lastName" 
                        class="emerald-input"
                        :class="{ 'error': !!editErrors.lastName }"
                      />
                      <small v-if="editErrors.lastName" class="field-error">{{ editErrors.lastName }}</small>
                    </div>
                  </div>
                  
                  <div class="field-group">
                    <label for="bio" class="field-label">Biographie</label>
                    <Textarea 
                      id="bio"
                      v-model="editForm.bio" 
                      rows="4"
                      placeholder="Parlez-nous de vous et de votre passion pour les TCG..."
                      class="emerald-input"
                    />
                  </div>
                  
                  <!-- 🆕 ADRESSE UTILISATEUR (OPTIONNELLE) -->
                  <div class="address-section">
                    <h4 class="section-title">
                      <i class="pi pi-map-marker"></i>
                      Adresse personnelle
                    </h4>
                    <p class="section-description">
                      Renseignez votre adresse pour faciliter vos futures demandes de rôles boutique
                    </p>
                    
                    <AddressAutocomplete
                      ref="profileAddressRef"
                      v-model="editForm.address"
                      mode="detailed"
                      label="Mon adresse"
                      placeholder="Rechercher votre adresse..."
                      field-id="profile-address"
                      :required="false"
                      :allow-remove="true"
                      @address-validated="handleAddressValidated"
                      @address-removed="handleAddressRemoved"
                      @validation-error="handleAddressError"
                    />
                  </div>
                  
                  <div class="form-actions">
                    <Button 
                      type="submit"
                      label="Sauvegarder"
                      icon="pi pi-check"
                      class="emerald-button primary"
                      :loading="isLoading"
                    />
                    <Button 
                      type="button"
                      label="Annuler"
                      icon="pi pi-times"
                      class="emerald-outline-btn cancel"
                      @click="cancelEdit"
                    />
                  </div>
                </form>
              </template>
            </Card>
          </section>

          <!-- Section changement de rôle -->
          <section class="role-request-section slide-in-up">
            <Card class="gaming-card role-card">
              <template #header>
                <div class="card-header-custom role-header" @click="toggleRoleSection">
                  <i class="pi pi-users header-icon"></i>
                  <h3 class="header-title">Gestion des rôles</h3>
                  <Button 
                    :icon="isRoleSectionExpanded ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"
                    class="collapse-btn"
                    @click.stop="toggleRoleSection"
                  />
                </div>
              </template>
              <template #content v-if="isRoleSectionExpanded">
                <div class="role-content">
                  <!-- Interface ADMIN -->
                  <div v-if="userRole === 'admin'" class="admin-role-management">
                    <div class="current-role">
                      <h4 class="role-section-title">Rôle actuel</h4>
                      <div class="current-role-display">
                        <span :class="['role-badge', 'large', userRole]">
                          <i :class="getRoleIcon(userRole)"></i>
                          {{ getRoleLabel(userRole) }}
                        </span>
                        <p class="role-description">
                          {{ getRoleDescription(userRole) }}
                        </p>
                      </div>
                    </div>
                    
                      <!-- Section admin enrichie à remplacer dans ProfileView.vue -->
                      <div class="admin-requests-section">
                        <h4 class="role-section-title">Demandes de rôles en attente</h4>
                        <p class="admin-info">Gérez les demandes de rôles des utilisateurs de la plateforme.</p>
                        
                        <div v-if="adminRequests.length === 0" class="empty-admin-requests">
                          <i class="pi pi-inbox empty-icon"></i>
                          <p>Aucune demande de rôle en attente</p>
                        </div>

                        <div v-else class="admin-requests-list">
                          <div 
                            v-for="request in adminRequests" 
                            :key="request.id"
                            class="admin-request-card"
                            :class="{ 'expanded': expandedRequest === request.id }"
                          >
                            <!-- En-tête cliquable -->
                            <div 
                              class="request-header" 
                              @click="toggleRequestDetails(request.id)"
                            >
                              <div class="request-user-info">
                                <h5>{{ request.user.pseudo }}</h5>
                                <span class="user-email">{{ request.user.email }}</span>
                              </div>
                              
                              <div class="request-summary">
                                <span :class="['role-badge', getRoleClassFromString(request.requestedRole)]">
                                  <i :class="getRoleIcon(getRoleClassFromString(request.requestedRole))"></i>
                                  {{ getRoleLabel(getRoleClassFromString(request.requestedRole)) }}
                                </span>
                                <span class="request-date">{{ formatDate(request.createdAt) }}</span>
                              </div>

                              <!-- Score de confiance -->
                              <div v-if="request.verification && request.verification.score !== null" class="confidence-score">
                                <div class="score-wrapper">
                                  <span :class="['score-badge', getScoreClass(request.verification.score)]">
                                    {{ request.verification.score }}/100
                                  </span>
                                  <span class="confidence-level">{{ getConfidenceLabel(request.verification.confidence_level) }}</span>
                                </div>
                              </div>

                              <div class="expand-icon">
                                <i :class="expandedRequest === request.id ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"></i>
                              </div>
                            </div>

                            <!-- Détails expandables -->
                            <div v-if="expandedRequest === request.id" class="request-details-expanded">
                              
                              <!-- Message de base -->
                              <div class="basic-details">
                                <h6>Message de motivation</h6>
                                <p class="request-message">{{ request.message }}</p>
                              </div>

                              <!-- Détails boutique si applicable -->
                              <div v-if="request.shop_data" class="shop-details">
                                <h6>
                                  <i class="pi pi-shop"></i>
                                  Informations boutique
                                </h6>
                                
                                <div class="shop-info-grid">
                                  <div class="info-item">
                                    <label>Nom de la boutique</label>
                                    <span>{{ request.shop_data.name }}</span>
                                  </div>
                                  
                                  <div class="info-item">
                                    <label>SIRET</label>
                                    <span class="siret-number">{{ formatSiret(request.shop_data.siret) }}</span>
                                  </div>
                                  
                                  <div class="info-item">
                                    <label>Téléphone</label>
                                    <span>{{ request.shop_data.phone }}</span>
                                  </div>
                                  
                                  <div class="info-item" v-if="request.shop_data.website">
                                    <label>Site web</label>
                                    <a :href="request.shop_data.website" target="_blank" class="website-link">
                                      {{ request.shop_data.website }}
                                      <i class="pi pi-external-link"></i>
                                    </a>
                                  </div>
                                  
                                  <div class="info-item full-width">
                                    <label>Adresse</label>
                                    <span>{{ request.shop_data.address?.full_address }}</span>
                                  </div>
                                </div>
                              </div>

                              <!-- Données de vérification -->
                              <div v-if="request.verification" class="verification-details">
                                <h6>
                                  <i class="pi pi-verified"></i>
                                  Vérification automatique
                                </h6>
                                
                                <div class="verification-grid">
                                  <!-- Score détaillé -->
                                  <div class="verification-score-detail">
                                    <div class="score-display">
                                      <div :class="['score-circle', getScoreClass(request.verification.score)]">
                                        <span class="score-value">{{ request.verification.score || 0 }}</span>
                                        <span class="score-max">/100</span>
                                      </div>
                                      <div class="score-info">
                                        <span class="confidence-text">{{ getConfidenceLabel(request.verification.confidence_level) }}</span>
                                        <span class="verification-date">
                                          Vérifié le {{ formatDate(request.verification.verification_date) }}
                                        </span>
                                      </div>
                                    </div>
                                  </div>

                                  <!-- Statuts de vérification -->
                                  <div class="verification-statuses">
                                    <div class="status-item">
                                      <div class="status-header">
                                        <i class="pi pi-building"></i>
                                        <span>Données INSEE</span>
                                      </div>
                                      <span :class="['status-badge', getStatusClass(request.verification.details?.insee_status)]">
                                        {{ getStatusLabel(request.verification.details?.insee_status) }}
                                      </span>
                                    </div>
                                    
                                    <div class="status-item">
                                      <div class="status-header">
                                        <i class="pi pi-map-marker"></i>
                                        <span>Google Places</span>
                                      </div>
                                      <span :class="['status-badge', getStatusClass(request.verification.details?.google_status)]">
                                        {{ getStatusLabel(request.verification.details?.google_status) }}
                                      </span>
                                    </div>
                                  </div>
                                </div>

                                <!-- Recommandations -->
                                <div v-if="request.verification.details?.recommendations" class="recommendations">
                                  <h6>Recommandations</h6>
                                  <ul class="recommendation-list">
                                    <li v-for="rec in request.verification.details.recommendations" :key="rec">
                                      <i class="pi pi-info-circle"></i>
                                      {{ rec }}
                                    </li>
                                  </ul>
                                </div>

                                <!-- Google Places embed -->
                                <div v-if="request.verification.google_place_id" class="google-places-embed">
                                  <h6>
                                    <i class="pi pi-map"></i>
                                    Localisation Google
                                  </h6>
                                  <div class="maps-embed-container">
                                    <Button 
                                      :label="`Voir ${request.shop_data?.name} sur Google Maps`"
                                      icon="pi pi-external-link"
                                      class="emerald-outline-btn maps-btn"
                                      @click="openGoogleMaps(request.shop_data?.name, request.shop_data?.address?.full_address)"
                                    />
                                    <small class="maps-note">
                                      Place ID: {{ request.verification.google_place_id }}
                                    </small>
                                  </div>
                                </div>
                              </div>

                              <!-- Actions admin -->
                              <div class="admin-actions-expanded">
                                <div class="action-group">
                                  <Button 
                                    label="Approuver"
                                    icon="pi pi-check"
                                    class="emerald-button primary"
                                    @click="approveRequest(request.id)"
                                  />
                                  <Button 
                                    label="Rejeter"
                                    icon="pi pi-times"
                                    severity="danger"
                                    @click="rejectRequest(request.id)"
                                  />
                                </div>
                                
                                <div class="action-secondary">
                                  <Button 
                                    label="Contacter l'utilisateur"
                                    icon="pi pi-envelope"
                                    class="emerald-outline-btn small"
                                    @click="contactUser(request.user.email)"
                                  />
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                  </div>
                  
                  <!-- Interface UTILISATEUR NORMAL -->
                  <div v-else class="user-role-management">
                    <div class="current-role">
                      <h4 class="role-section-title">Rôle actuel</h4>
                      <div class="current-role-display">
                        <span :class="['role-badge', 'large', userRole]">
                          <i :class="getRoleIcon(userRole)"></i>
                          {{ getRoleLabel(userRole) }}
                        </span>
                        <p class="role-description">
                          {{ getRoleDescription(userRole) }}
                        </p>
                      </div>
                    </div>
                    
                    <div v-if="userRole === 'user'" class="role-upgrade">
                      <!-- ÉTAT INITIAL : Choix du rôle -->
                      <div v-if="!roleRequestMode">
                        <h4 class="role-section-title">Demander un nouveau rôle</h4>
                        <div class="role-options">
                          
                          <!-- Option Organisateur -->
                          <div class="role-option">
                            <div class="role-option-header">
                              <span class="role-badge organizer">
                                <i class="pi pi-calendar"></i>
                                Organisateur
                              </span>
                              <span class="role-benefits">Organiser des tournois et événements</span>
                            </div>
                            <p class="role-option-description">
                              Créez et gérez des tournois, organisez des événements communautaires, 
                              et animez la scène TCG locale.
                            </p>
                            <Button 
                              label="Demander le rôle"
                              icon="pi pi-calendar"
                              class="emerald-outline-btn role-request-btn"
                              @click="startRoleRequest('organizer')"
                              :disabled="hasRequestPending('organizer')"
                            />
                          </div>
                          
                          <!-- Option Boutique -->
                          <div class="role-option">
                            <div class="role-option-header">
                              <span class="role-badge shop">
                                <i class="pi pi-shop"></i>
                                Boutique
                              </span>
                              <span class="role-benefits">Vendre des produits et organiser des événements</span>
                            </div>
                            <p class="role-option-description">
                              Gérez votre boutique physique, vendez des produits, organisez des tournois 
                              et événements dans votre établissement.
                            </p>
                            <Button 
                              label="Demander le rôle"
                              icon="pi pi-shop"
                              class="emerald-outline-btn role-request-btn"
                              @click="startRoleRequest('shop')"
                              :disabled="hasRequestPending('shop')"
                            />
                          </div>
                        </div>
                      </div>

                      <!-- ÉTAT FORMULAIRE : Demande organisateur -->
                      <div v-else-if="roleRequestMode === 'organizer'" class="role-request-form slide-in-up">
                        <div class="form-header">
                          <Button 
                            icon="pi pi-arrow-left"
                            class="p-button-text back-btn"
                            @click="cancelRoleRequest"
                          />
                          <h4 class="role-section-title">
                            <i class="pi pi-calendar"></i>
                            Demande de rôle Organisateur
                          </h4>
                        </div>
                        
                        <div class="request-form-content">
                          <div class="field-group">
                            <label for="organizerMessage" class="field-label">Motivation *</label>
                            <Textarea 
                              id="organizerMessage"
                              v-model="organizerRequestForm.message"
                              placeholder="Expliquez votre motivation, votre expérience dans l'organisation d'événements TCG, vos projets..."
                              rows="5"
                              class="emerald-input"
                              :class="{ 'error': !!roleRequestErrors.message }"
                            />
                            <small v-if="roleRequestErrors.message" class="field-error">{{ roleRequestErrors.message }}</small>
                            <small v-else class="field-help">Minimum 30 caractères - Décrivez votre expérience et vos projets</small>
                          </div>
                          
                          <div class="form-actions">
                            <Button 
                              label="Annuler"
                              icon="pi pi-times"
                              class="emerald-outline-btn cancel"
                              @click="cancelRoleRequest"
                            />
                            <Button 
                              label="Soumettre la demande"
                              icon="pi pi-send"
                              class="emerald-button primary"
                              @click="prepareRoleRequestSubmission"
                              :disabled="!organizerRequestForm.message.trim()"
                            />
                          </div>
                        </div>
                      </div>

                      <!-- 🆕 ÉTAT FORMULAIRE : Demande boutique avec AddressAutocomplete -->
                      <div v-else-if="roleRequestMode === 'shop'" class="role-request-form slide-in-up">
                        <div class="form-header">
                          <Button 
                            icon="pi pi-arrow-left"
                            class="p-button-text back-btn"
                            @click="cancelRoleRequest"
                          />
                          <h4 class="role-section-title">
                            <i class="pi pi-shop"></i>
                            Demande de rôle Boutique
                          </h4>
                        </div>
                        
                        <div class="request-form-content">
                          <div class="form-section">
                            <h5 class="section-subtitle">
                              <i class="pi pi-building"></i>
                              Informations boutique
                            </h5>
                            
                            <div class="form-grid">
                              <div class="field-group">
                                <label for="shopName" class="field-label">Nom de la boutique *</label>
                                <InputText 
                                  id="shopName"
                                  v-model="shopRequestForm.shopName"
                                  placeholder="ex: Gaming Paradise"
                                  class="emerald-input"
                                  :class="{ 'error': !!roleRequestErrors.shopName }"
                                />
                                <small v-if="roleRequestErrors.shopName" class="field-error">{{ roleRequestErrors.shopName }}</small>
                              </div>
                              
                              <div class="field-group">
                                <label for="siretNumber" class="field-label">Numéro SIRET *</label>
                                <InputText 
                                  id="siretNumber"
                                  v-model="shopRequestForm.siretNumber"
                                  placeholder="12345678901234"
                                  class="emerald-input"
                                  :class="{ 'error': !!roleRequestErrors.siretNumber }"
                                  maxlength="17"
                                />
                                <small v-if="roleRequestErrors.siretNumber" class="field-error">{{ roleRequestErrors.siretNumber }}</small>
                                <small v-else class="field-help">14 chiffres, nécessaire pour vérifier votre entreprise</small>
                              </div>
                            </div>
                          </div>
                          
                          <!-- 🆕 ADRESSE BOUTIQUE AVEC AUTOCOMPLÉTION -->
                          <div class="form-section">
                            <h5 class="section-subtitle">
                              <i class="pi pi-map-marker"></i>
                              Adresse de la boutique
                            </h5>
                            
                            <!-- Message si adresse utilisateur disponible -->
                            <div v-if="user.address && !shopRequestForm.shopAddress" class="address-prefill-notice">
                              <div class="notice-content">
                                <i class="pi pi-info-circle"></i>
                                <div class="notice-text">
                                  <strong>Adresse trouvée dans votre profil</strong>
                                  <p>{{ user.address.fullAddress }}</p>
                                </div>
                                <Button
                                  label="Utiliser cette adresse"
                                  icon="pi pi-copy"
                                  class="emerald-outline-btn small"
                                  @click="prefillShopAddress"
                                />
                              </div>
                            </div>
                            
                            <AddressAutocomplete
                              ref="shopAddressRef"
                              v-model="shopRequestForm.shopAddress"
                              mode="detailed"
                              label="Adresse complète de la boutique"
                              placeholder="Rechercher l'adresse de votre boutique..."
                              field-id="shop-address"
                              :required="true"
                              :allow-remove="false"
                              @address-validated="handleShopAddressValidated"
                              @validation-error="handleShopAddressError"
                            />
                            
                            <small v-if="roleRequestErrors.shopAddress" class="field-error">{{ roleRequestErrors.shopAddress }}</small>
                          </div>
                          
                          <div class="form-section">
                            <h5 class="section-subtitle">
                              <i class="pi pi-phone"></i>
                              Contact
                            </h5>
                            
                            <div class="form-grid">
                              <div class="field-group">
                                <label for="shopPhone" class="field-label">Téléphone *</label>
                                <InputText 
                                  id="shopPhone"
                                  v-model="shopRequestForm.shopPhone"
                                  placeholder="01 23 45 67 89"
                                  class="emerald-input"
                                  :class="{ 'error': !!roleRequestErrors.shopPhone }"
                                />
                                <small v-if="roleRequestErrors.shopPhone" class="field-error">{{ roleRequestErrors.shopPhone }}</small>
                              </div>
                              
                              <div class="field-group">
                                <label for="shopWebsite" class="field-label">Site web</label>
                                <InputText 
                                  id="shopWebsite"
                                  v-model="shopRequestForm.shopWebsite"
                                  placeholder="https://www.maboutique.com"
                                  class="emerald-input"
                                />
                                <small class="field-help">Optionnel mais recommandé</small>
                              </div>
                            </div>
                          </div>
                          
                          <div class="form-section">
                            <h5 class="section-subtitle">
                              <i class="pi pi-comment"></i>
                              Présentation
                            </h5>
                            
                            <div class="field-group">
                              <label for="shopMessage" class="field-label">Décrivez votre boutique *</label>
                              <Textarea 
                                id="shopMessage"
                                v-model="shopRequestForm.message"
                                placeholder="Parlez-nous de votre boutique, vos spécialités TCG, votre expérience, les événements que vous organisez..."
                                rows="4"
                                class="emerald-input"
                                :class="{ 'error': !!roleRequestErrors.message }"
                              />
                              <small v-if="roleRequestErrors.message" class="field-error">{{ roleRequestErrors.message }}</small>
                              <small v-else class="field-help">Minimum 50 caractères - Détaillez vos spécialités et services</small>
                            </div>
                          </div>
                          
                          <div class="form-actions">
                            <Button 
                              label="Annuler"
                              icon="pi pi-times"
                              class="emerald-outline-btn cancel"
                              @click="cancelRoleRequest"
                            />
                            <Button 
                              label="Soumettre la demande"
                              icon="pi pi-send"
                              class="emerald-btn"
                              @click="prepareRoleRequestSubmission"
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Demandes en cours -->
                    <div v-if="roleRequests.length > 0" class="pending-requests">
                      <h4 class="role-section-title">Demandes en cours</h4>
                      <div class="request-list">
                        <div 
                          v-for="request in roleRequests" 
                          :key="request.id"
                          class="request-item"
                        >
                          <div class="request-info">
                            <span :class="['role-badge', getRoleClassFromString(request.requestedRole)]">
                              <i :class="getRoleIcon(getRoleClassFromString(request.requestedRole))"></i>
                              {{ getRoleLabel(getRoleClassFromString(request.requestedRole)) }}
                            </span>
                            <span class="request-date">
                              Demandé le {{ formatDate(request.createdAt) }}
                            </span>
                          </div>
                          <span :class="['request-status', request.status]">
                            {{ getStatusLabel(request.status) }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </template>
            </Card>
          </section>
        </div>

        <!-- Sidebar droite (1/3) -->
        <aside class="profile-sidebar">
          
<!-- Widget Activité récente -->
<Card class="sidebar-card activity-card slide-in-down">
  <template #header>
    <div class="card-header-custom activity-header">
      <i class="pi pi-history header-icon"></i>
      <h3 class="header-title">Activité récente</h3>
    </div>
  </template>
  <template #content>
    <div class="activity-list">
      <!-- Liste des notifications récentes -->
      <div 
        v-for="notification in recentNotifications" 
        :key="notification.id"
        class="activity-item"
        :class="{ 'activity-unread': !notification.isRead }"
        @click="handleActivityClick(notification)"
      >
        <div class="activity-icon" :class="{ 'unread-icon': !notification.isRead }">
          <span class="notification-emoji">{{ notification.icon || '🔔' }}</span>
        </div>
        <div class="activity-content">
          <p class="activity-text">{{ notification.message }}</p>
          <span class="activity-time">{{ notification.timeAgo }}</span>
        </div>
        <div v-if="!notification.isRead" class="unread-indicator"></div>
      </div>
      
      <!-- État vide -->
      <div v-if="recentNotifications.length === 0 && !isLoadingActivity" class="empty-activity">
        <i class="pi pi-inbox empty-icon"></i>
        <p class="empty-text">Aucune activité récente</p>
      </div>
      
      <!-- Loading state -->
      <div v-if="isLoadingActivity" class="loading-activity">
        <i class="pi pi-spin pi-spinner"></i>
        <p>Chargement de l'activité...</p>
      </div>
      
      <!-- Bouton Charger plus -->
      <div v-if="pagination.hasMore && recentNotifications.length > 0" class="load-more-section">
        <Button 
          label="Charger plus"
          icon="pi pi-chevron-down"
          class="load-more-btn"
          @click="loadMoreActivity"
          :loading="isLoadingActivity"
          text
        />
      </div>
    </div>
  </template>
</Card>
          
          <!-- Widget Mes Topics -->
          <Card class="sidebar-card topics-card slide-in-down">
            <template #header>
              <div class="card-header-custom topics-header">
                <i class="pi pi-comments header-icon"></i>
                <h3 class="header-title">Mes topics</h3>
              </div>
            </template>
            <template #content>
              <div class="topics-summary">
                <div class="topic-stats">
                  <div class="topic-stat">
                    <span class="stat-value">{{ user.stats?.topicsCreated || 0 }}</span>
                    <span class="stat-label">Créés</span>
                  </div>
                  <div class="topic-stat">
                    <span class="stat-value">{{ user.stats?.topicsParticipated || 0 }}</span>
                    <span class="stat-label">Participés</span>
                  </div>
                </div>
                
                <Button 
                  label="Voir tous mes topics"
                  icon="pi pi-external-link"
                  class="emerald-outline-btn small"
                  @click="goToMyTopics"
                />
              </div>
            </template>
          </Card>
          
          <!-- Widget Événements (si organisateur/boutique) -->
          <Card 
            v-if="['organizer', 'shop'].includes(userRole)" 
            class="sidebar-card events-card slide-in-down"
          >
            <template #header>
              <div class="card-header-custom events-header">
                <i class="pi pi-calendar header-icon"></i>
                <h3 class="header-title">Mes événements</h3>
              </div>
            </template>
            <template #content>
              <div class="events-summary">
                <div class="event-stats">
                  <div class="event-stat">
                    <span class="stat-value">{{ user.stats?.eventsActive || 0 }}</span>
                    <span class="stat-label">Actifs</span>
                  </div>
                  <div class="event-stat">
                    <span class="stat-value">{{ user.stats?.eventsTotal || 0 }}</span>
                    <span class="stat-label">Total</span>
                  </div>
                </div>
                
                <div class="event-actions">
                  <Button 
                    label="Créer un événement"
                    icon="pi pi-plus"
                    class="emerald-btn small"
                    @click="createEvent"
                  />
                  <Button 
                    label="Gérer mes événements"
                    icon="pi pi-cog"
                    class="emerald-outline-btn small"
                    @click="manageEvents"
                  />
                </div>
              </div>
            </template>
          </Card>
        </aside>
      </div>
    </div>
    
    <!-- Modal de confirmation soumission -->
    <Dialog 
      v-model:visible="showConfirmSubmitModal"
      modal
      header="Confirmer la demande"
      :style="{ width: '500px' }"
      class="emerald-modal"
    >
      <div class="confirm-content">
        <i class="pi pi-exclamation-triangle confirm-icon"></i>
        <div class="confirm-text">
          <p><strong>Êtes-vous sûr de vouloir soumettre cette demande ?</strong></p>
          <p>Une fois envoyée, vous devrez attendre la réponse d'un administrateur avant de pouvoir faire une nouvelle demande de changement de rôle.</p>
        </div>
      </div>
      <template #footer>
        <Button 
          label="Annuler" 
          icon="pi pi-times" 
          class="emerald-outline-btn cancel"
          @click="showConfirmSubmitModal = false" 
        />
        <Button 
          label="Confirmer" 
          icon="pi pi-check" 
          class="emerald-btn"
          @click="confirmRoleRequestSubmission"
          :loading="isSubmittingRoleRequest"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import api from '../services/api'
// 🆕 Import du composant AddressAutocomplete
import AddressAutocomplete from '../components/form/AddressAutocomplete.vue'
import { useNotifications } from '../composables/useNotifications'

// Stores et router
const authStore = useAuthStore()
const router = useRouter()
const toast = useToast()

// State
const editMode = ref(false)
const isLoading = ref(false)
const avatarFile = ref(null)
const avatarPreview = ref(null)
const isRoleSectionExpanded = ref(false)
const adminRequests = ref([])
const isLoadingRequests = ref(false)
const profileAddressRef = ref(null)
const shopAddressRef = ref(null)
const expandedRequest = ref(null)

// Gestion des demandes de rôles inline
const roleRequestMode = ref(null) // null, 'organizer', 'shop'
const showConfirmSubmitModal = ref(false)
const isSubmittingRoleRequest = ref(false)

// Formulaire organisateur
const organizerRequestForm = reactive({
  message: ''
})

// 🆕 Formulaire boutique avec adresse
const shopRequestForm = reactive({
  shopName: '',
  siretNumber: '',
  shopAddress: null, // 🔄 Objet Address au lieu de string
  shopPhone: '',
  shopWebsite: '',
  message: ''
})

// Erreurs communes
const roleRequestErrors = reactive({})

// 🆕 Formulaire d'édition avec adresse
const editForm = reactive({
  pseudo: '',
  firstName: '',
  lastName: '',
  bio: '',
  favoriteClass: '',
  address: null // 🔄 Objet Address au lieu de string
})

const editErrors = reactive({
  pseudo: '',
  firstName: '',
  lastName: '',
  bio: ''
})

// Données utilisateur réelles depuis le store
const user = computed(() => authStore.user || {})
const roleRequests = ref([])

// 🆕 Computed pour validation formulaire boutique
const isShopFormValid = computed(() => {
  return shopRequestForm.shopName.trim() && 
         shopRequestForm.siretNumber.trim() && 
         shopRequestForm.shopAddress && 
         shopRequestForm.shopPhone.trim() && 
         shopRequestForm.message.trim()
})

// Computed existants
const isCurrentUser = computed(() => {
  return authStore.user?.id === user.value.id
})

// 🆕 Composable notifications pour l'activité
const {
  recentNotifications,
  pagination,
  loadRecentNotifications,
  loadMore,
  isLoading: isLoadingActivity,
  handleNotificationClick
} = useNotifications()

// 🆕 Gestion du clic sur une notification dans l'activité
const handleActivityClick = async (notification) => {
  const wasUnread = !notification.isRead  // 🆕 Capturer l'état avant
  
  await handleNotificationClick(notification)
  
  // 🆕 Afficher le toast seulement si la notification était non lue
  if (wasUnread) {
    toast.add({
      severity: 'info',
      summary: notification.title,
      detail: 'Notification marquée comme lue',
      life: 2000
    })
  }
}

// 🆕 Charger plus d'activité
const loadMoreActivity = async () => {
  if (pagination.value.hasMore && !isLoadingActivity.value) {
    try {
      await loadMore()
    } catch (error) {
      toast.add({
        severity: 'error',
        summary: 'Erreur',
        detail: 'Impossible de charger plus d\'activité',
        life: 3000
      })
    }
  }
}

const userRole = computed(() => {
  const roles = user.value.roles || []
  if (roles.includes('ROLE_ADMIN')) return 'admin'
  if (roles.includes('ROLE_SHOP')) return 'shop'
  if (roles.includes('ROLE_ORGANIZER')) return 'organizer'
  return 'user'
})

const backendUrl = computed(() => import.meta.env.VITE_BACKEND_URL)

// Methods
const getRoleIcon = (role) => {
  const icons = {
    user: 'pi pi-user',
    organizer: 'pi pi-calendar',
    shop: 'pi pi-shop',
    admin: 'pi pi-shield'
  }
  return icons[role] || 'pi pi-user'
}

const getRoleLabel = (role) => {
  const labels = {
    user: 'Utilisateur',
    organizer: 'Organisateur', 
    shop: 'Boutique',
    admin: 'Administrateur'
  }
  return labels[role] || 'Utilisateur'
}

const getRoleDescription = (role) => {
  const descriptions = {
    user: 'Participant aux discussions et événements de la communauté.',
    organizer: 'Organisateur d\'événements et de tournois TCG.',
    shop: 'Boutique physique proposant produits et événements.',
    admin: 'Administrateur de la plateforme.'
  }
  return descriptions[role] || 'Rôle utilisateur standard.'
}

const getRoleClassFromString = (roleString) => {
  if (roleString === 'ROLE_ORGANIZER') return 'organizer'
  if (roleString === 'ROLE_SHOP') return 'shop'
  if (roleString === 'ROLE_ADMIN') return 'admin'
  return 'user'
}

const hasRequestPending = (role) => {
  return roleRequests.value.some(req => req.requestedRole === `ROLE_${role.toUpperCase()}` && req.status === 'pending')
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR')
}

// 🆕 Handlers pour AddressAutocomplete dans l'édition de profil
const handleAddressValidated = (address) => {
  console.log('✅ Adresse utilisateur validée:', address)
  editForm.address = address
}

const handleAddressRemoved = () => {
  console.log('🗑️ Adresse utilisateur supprimée')
  editForm.address = null
}

const handleAddressError = (errors) => {
  console.warn('❌ Erreurs validation adresse utilisateur:', errors)
  toast.add({
    severity: 'warn',
    summary: 'Validation adresse',
    detail: 'Veuillez corriger les erreurs dans l\'adresse',
    life: 3000
  })
}

// 🆕 Handlers pour AddressAutocomplete dans demande boutique
const handleShopAddressValidated = (address) => {
  console.log('✅ Adresse boutique validée:', address)
  shopRequestForm.shopAddress = address
  // Clear les erreurs d'adresse
  roleRequestErrors.shopAddress = ''
}

const handleShopAddressError = (errors) => {
  console.warn('❌ Erreurs validation adresse boutique:', errors)
  roleRequestErrors.shopAddress = 'Veuillez saisir une adresse valide'
}

// 🆕 Pré-remplir l'adresse boutique avec l'adresse utilisateur
const prefillShopAddress = () => {
  if (user.value.address) {
    shopRequestForm.shopAddress = { ...user.value.address }
    toast.add({
      severity: 'info',
      summary: 'Adresse pré-remplie',
      detail: 'Adresse de votre profil utilisée pour la boutique',
      life: 3000
    })
  }
}

// Actions existantes
const loadUserProfile = async () => {
  try {
    const response = await api.get('/api/profile')
    // Mettre à jour le store avec les données fraîches
    authStore.user = response.data
    roleRequests.value = response.data.roleRequests || []
    
    // 🆕 Initialiser l'adresse dans le formulaire d'édition
    editForm.address = response.data.address || null
    
  } catch (error) {
    console.error('Erreur lors du chargement du profil:', error)
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger le profil', life: 3000 })
  }
}

const loadAdminRequests = async () => {
  if (userRole.value !== 'admin') return
  
  isLoadingRequests.value = true
  try {
    const response = await api.get('/api/admin/role-requests')
    adminRequests.value = response.data.requests
    console.log('📋 Demandes admin chargées:', response.data)
  } catch (error) {
    console.error('Erreur chargement demandes admin:', error)
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger les demandes', life: 3000 })
  } finally {
    isLoadingRequests.value = false
  }
}

const openAvatarEditor = () => {
  // Déclencher le sélecteur de fichier
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = 'image/*'
  input.onchange = (e) => {
    const file = e.target.files[0]
    if (file) {
      avatarFile.value = file
      // Prévisualisation
      const reader = new FileReader()
      reader.onload = (e) => {
        avatarPreview.value = e.target.result
      }
      reader.readAsDataURL(file)
      // Upload immédiat
      uploadAvatar()
    }
  }
  input.click()
}

const uploadAvatar = async () => {
  if (!avatarFile.value) return
  
  const formData = new FormData()
  formData.append('avatar', avatarFile.value)
  
  isLoading.value = true
  try {
    const response = await api.post('/api/profile/avatar', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    // Mettre à jour le store
    authStore.user.avatar = response.data.avatar
    avatarPreview.value = null
    avatarFile.value = null
    
    toast.add({ severity: 'success', summary: 'Avatar mis à jour', detail: 'Votre photo de profil a été mise à jour', life: 3000 })
  } catch (error) {
    console.error('Erreur upload avatar:', error)
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de mettre à jour l\'avatar', life: 3000 })
  } finally {
    isLoading.value = false
  }
}

const cancelEdit = () => {
  editMode.value = false
  // Reset form avec les données actuelles
  editForm.pseudo = user.value.pseudo || ''
  editForm.firstName = user.value.firstName || ''
  editForm.lastName = user.value.lastName || ''
  editForm.bio = user.value.bio || ''
  editForm.favoriteClass = user.value.favoriteClass || ''
  editForm.address = user.value.address || null // 🆕 Reset adresse
  // Clear errors
  Object.keys(editErrors).forEach(key => editErrors[key] = '')
}

// 🆕 Sauvegarde profil avec adresse
const saveProfile = async () => {
  // Validation
  Object.keys(editErrors).forEach(key => editErrors[key] = '')
  
  if (!editForm.pseudo || editForm.pseudo.length < 3) {
    editErrors.pseudo = 'Le pseudo doit contenir au moins 3 caractères'
    return
  }
  
  isLoading.value = true
  
  try {
    // 🆕 Valider l'adresse automatiquement si présente
    if (profileAddressRef.value && editForm.address) {
      try {
        const result = await profileAddressRef.value.validateAddress()
        editForm.address = result.address
      } catch (error) {
        toast.add({
          severity: 'error',
          summary: 'Erreur adresse',
          detail: 'Veuillez corriger l\'adresse avant de sauvegarder',
          life: 3000
        })
        isLoading.value = false
        return
      }
    }
    
    const payload = {
      pseudo: editForm.pseudo,
      firstName: editForm.firstName,
      lastName: editForm.lastName,
      bio: editForm.bio,
      favoriteClass: editForm.favoriteClass,
      address: editForm.address
    }
    
    const response = await api.put('/api/profile/update', payload)
    
    // Mise à jour du store
    Object.assign(authStore.user, response.data.user)
    
    editMode.value = false
    toast.add({ 
      severity: 'success', 
      summary: 'Profil mis à jour', 
      detail: 'Vos informations ont été sauvegardées', 
      life: 3000 
    })
  } catch (error) {
    const errorMsg = error.response?.data?.error || 'Impossible de sauvegarder le profil'
    toast.add({ severity: 'error', summary: 'Erreur', detail: errorMsg, life: 3000 })
  } finally {
    isLoading.value = false
  }
}

const startRoleRequest = (role) => {
  roleRequestMode.value = role
  // Reset erreurs
  Object.keys(roleRequestErrors).forEach(key => roleRequestErrors[key] = '')
  
  // Reset formulaires
  if (role === 'organizer') {
    organizerRequestForm.message = ''
  } else if (role === 'shop') {
    Object.keys(shopRequestForm).forEach(key => {
      if (key === 'shopAddress') {
        shopRequestForm[key] = null // 🆕 Reset à null pour objet Address
      } else {
        shopRequestForm[key] = ''
      }
    })
  }
}

const cancelRoleRequest = () => {
  roleRequestMode.value = null
  Object.keys(roleRequestErrors).forEach(key => roleRequestErrors[key] = '')
}

// 🆕 Validation mise à jour pour adresse boutique
const validateRoleRequest = () => {
  Object.keys(roleRequestErrors).forEach(key => roleRequestErrors[key] = '')
  let isValid = true
  
  if (roleRequestMode.value === 'organizer') {
    if (!organizerRequestForm.message.trim()) {
      roleRequestErrors.message = 'Veuillez expliquer votre motivation'
      isValid = false
    } else if (organizerRequestForm.message.length < 30) {
      roleRequestErrors.message = 'Le message doit contenir au moins 30 caractères'
      isValid = false
    }
  } else if (roleRequestMode.value === 'shop') {
    if (!shopRequestForm.shopName.trim()) {
      roleRequestErrors.shopName = 'Le nom de la boutique est obligatoire'
      isValid = false
    }
    
    if (!shopRequestForm.siretNumber.trim()) {
      roleRequestErrors.siretNumber = 'Le numéro SIRET est obligatoire'
      isValid = false
    } else if (!/^\d{14}$/.test(shopRequestForm.siretNumber.replace(/\s/g, ''))) {
      roleRequestErrors.siretNumber = 'Le SIRET doit contenir exactement 14 chiffres'
      isValid = false
    }
    
    // 🆕 Validation adresse boutique
    if (!shopRequestForm.shopAddress) {
      roleRequestErrors.shopAddress = 'L\'adresse de la boutique est obligatoire'
      isValid = false
    }
    
    if (!shopRequestForm.shopPhone.trim()) {
      roleRequestErrors.shopPhone = 'Le téléphone est obligatoire'
      isValid = false
    }
    
    if (!shopRequestForm.message.trim()) {
      roleRequestErrors.message = 'Veuillez expliquer votre demande'
      isValid = false
    } else if (shopRequestForm.message.length < 50) {
      roleRequestErrors.message = 'Le message doit contenir au moins 50 caractères'
      isValid = false
    }
  }
  
  return isValid
}

const prepareRoleRequestSubmission = () => {
  if (!validateRoleRequest()) return
  showConfirmSubmitModal.value = true
}

// 🆕 Soumission mise à jour avec adresse boutique
const confirmRoleRequestSubmission = async () => {
  showConfirmSubmitModal.value = false
  isSubmittingRoleRequest.value = true
  
  try {
    // 🆕 Valider l'adresse boutique automatiquement si demande shop
    if (roleRequestMode.value === 'shop' && shopAddressRef.value) {
      try {
        const result = await shopAddressRef.value.validateAddress()
        shopRequestForm.shopAddress = result.address
      } catch (error) {
        toast.add({
          severity: 'error',
          summary: 'Erreur adresse boutique',
          detail: 'Veuillez corriger l\'adresse de la boutique',
          life: 3000
        })
        isSubmittingRoleRequest.value = false
        return
      }
    }
    
    const payload = {
      role: roleRequestMode.value === 'organizer' ? 'ROLE_ORGANIZER' : 'ROLE_SHOP',
      message: roleRequestMode.value === 'organizer' ? organizerRequestForm.message : shopRequestForm.message
    }
    
    // Ajouter les données boutique avec adresse validée
    if (roleRequestMode.value === 'shop') {
      payload.shopName = shopRequestForm.shopName
      payload.siretNumber = shopRequestForm.siretNumber.replace(/\s/g, '')
      payload.shopAddress = shopRequestForm.shopAddress
      payload.shopPhone = shopRequestForm.shopPhone
      payload.shopWebsite = shopRequestForm.shopWebsite
    }
    
    await api.post('/api/profile/request-role', payload)
    
    roleRequestMode.value = null
    await loadUserProfile()
    
    toast.add({
      severity: 'success',
      summary: 'Demande envoyée',
      detail: 'Votre demande de changement de rôle a été envoyée avec succès',
      life: 4000
    })
  } catch (error) {
    const errorMsg = error.response?.data?.error || 'Impossible d\'envoyer la demande'
    toast.add({ 
      severity: 'error', 
      summary: 'Erreur', 
      detail: errorMsg, 
      life: 3000 
    })
  } finally {
    isSubmittingRoleRequest.value = false
  }
}

// Méthodes pour l'affichage des données enrichies
const getScoreClass = (score) => {
  if (score >= 80) return 'score-high'
  if (score >= 60) return 'score-medium'
  if (score >= 40) return 'score-low'
  return 'score-very-low'
}

const getConfidenceLabel = (level) => {
  const labels = {
    'high': 'Très fiable',
    'medium': 'Fiable',
    'low': 'Peu fiable',
    'very_low': 'Non fiable',
    'unknown': 'Non vérifié'
  }
  return labels[level] || 'Non vérifié'
}

const getStatusClass = (status) => {
  if (status === 'found' || status === 'available') return 'status-success'
  if (status === 'unavailable' || status === 'not_found') return 'status-warning'
  return 'status-error'
}

const getStatusLabel = (status) => {
  const labels = {
    'found': 'Trouvé',
    'not_found': 'Non trouvé',
    'available': 'Disponible',
    'unavailable': 'Indisponible',
    'error': 'Erreur'
  }
  return labels[status] || 'Inconnu'
}

const formatSiret = (siret) => {
  if (!siret) return ''
  const cleaned = siret.replace(/\s/g, '')
  return cleaned.replace(/(\d{3})(\d{3})(\d{3})(\d{5})/, '$1 $2 $3 $4')
}

const toggleRequestDetails = (requestId) => {
  expandedRequest.value = expandedRequest.value === requestId ? null : requestId
}

const openGoogleMaps = (shopName, address) => {
  const query = encodeURIComponent(`${shopName} ${address}`)
  const url = `https://www.google.com/maps/search/${query}`
  window.open(url, '_blank')
}

const approveRequest = async (requestId) => {
  try {
    await api.post(`/api/admin/role-requests/${requestId}/approve`)
    toast.add({
      severity: 'success',
      summary: 'Demande approuvée',
      detail: 'La demande de rôle a été approuvée avec succès',
      life: 3000
    })
    await loadAdminRequests() // Recharger les demandes
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible d\'approuver la demande',
      life: 3000
    })
  }
}

const rejectRequest = async (requestId) => {
  try {
    await api.post(`/api/admin/role-requests/${requestId}/reject`)
    toast.add({
      severity: 'success',
      summary: 'Demande rejetée',
      detail: 'La demande de rôle a été rejetée',
      life: 3000
    })
    await loadAdminRequests() // Recharger les demandes
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de rejeter la demande',
      life: 3000
    })
  }
}

const contactUser = (email) => {
  window.location.href = `mailto:${email}?subject=Concernant votre demande de rôle sur MULLIGAN TCG`
}

const goToMyTopics = () => {
  router.push('/my-topics')
}

const createEvent = () => {
  router.push('/events/create')
}

const manageEvents = () => {
  router.push('/events/manage')
}

const toggleRoleSection = () => {
  isRoleSectionExpanded.value = !isRoleSectionExpanded.value
}

// Lifecycle
onMounted(async () => {
  if (userRole.value === 'admin') {
    await loadAdminRequests()
  }

  // Charger les données du profil
  await loadUserProfile()
  
    try {
    await loadRecentNotifications(1, true) // Page 1, reset
  } catch (error) {
    console.error('Erreur chargement activité:', error)
  }

  // Initialiser le formulaire d'édition
  editForm.pseudo = user.value.pseudo || ''
  editForm.firstName = user.value.firstName || ''
  editForm.lastName = user.value.lastName || ''
  editForm.bio = user.value.bio || ''
  editForm.favoriteClass = user.value.favoriteClass || ''
  editForm.address = user.value.address || null // 🆕 Initialiser adresse
  
  console.log('🔍 Profil chargé pour:', user.value.pseudo)
})
</script>

<style scoped>
/* === PROFILE PAGE EMERALD GAMING === */

.profile-page {
  min-height: calc(100vh - 140px);
  background: var(--surface-gradient);
  padding: 2rem 0;
  margin-top: 140px; /* Assure que le contenu commence sous le header */
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

.profile-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 2rem;
  align-items: start;
}

/* Main profile column */
.main-profile {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* Profile header */
.profile-header-card {
  position: relative;
}

.profile-header-content {
  padding: 2rem;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
}

.avatar-section {
  display: flex;
  gap: 2rem;
  align-items: center;
  flex: 1;
}

.avatar-container {
  position: relative;
}

:deep(.profile-avatar) {
  width: 120px !important;
  height: 120px !important;
  font-size: 3rem !important;
  background: var(--emerald-gradient) !important;
  color: white !important;
  border: 4px solid white !important;
  box-shadow: var(--shadow-medium) !important;
}

/* Avatar image */
.avatar-image {
  width: 120px !important;
  height: 120px !important;
  border-radius: 50% !important;
  object-fit: cover !important;
  border: 4px solid white !important;
  box-shadow: var(--shadow-medium) !important;
  aspect-ratio: 1 / 1 !important;
}

/* Avatar preview */
.avatar-preview {
  width: 120px !important;
  height: 120px !important;
  border-radius: 50% !important;
  object-fit: cover !important;
  border: 4px solid white !important;
  box-shadow: var(--shadow-medium) !important;
}

.avatar-edit-btn {
  position: absolute;
  bottom: 8px;
  right: 8px;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--primary);
  color: white;
  border: 2px solid white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all var(--transition-fast);
  box-shadow: var(--shadow-small);
}

.avatar-edit-btn:hover {
  background: var(--primary-dark);
  transform: scale(1.1);
}

.basic-info {
  flex: 1;
}

.username-section {
  margin-bottom: 1.5rem;
}

.username {
  font-size: 2rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 0.75rem 0;
}

.role-badges {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.role-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.role-badge.user {
  background: rgba(84, 110, 122, 0.1);
  color: var(--secondary);
  border: 1px solid rgba(84, 110, 122, 0.2);
}

.role-badge.organizer {
  background: rgba(38, 166, 154, 0.1);
  color: var(--primary);
  border: 1px solid rgba(38, 166, 154, 0.2);
}

.role-badge.shop {
  background: rgba(255, 87, 34, 0.1);
  color: var(--accent);
  border: 1px solid rgba(255, 87, 34, 0.2);
}

.role-badge.admin {
  background: linear-gradient(135deg, #8b5cf6, #a855f7);
  color: white;
  border: none;
}

.role-badge.large {
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
}

.verified-badge {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.profile-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.5rem;
}

.stat-item {
  text-align: center;
}

.stat-value {
  display: block;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--primary);
  line-height: 1;
}

.stat-label {
  display: block;
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin-top: 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.quick-actions {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
}

/* Edit section */
.edit-header {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
}

.edit-form {
  padding: 2rem;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.field-label {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* 🆕 Section adresse */
.address-section {
  background: rgba(38, 166, 154, 0.05);
  border: 1px solid rgba(38, 166, 154, 0.1);
  border-radius: var(--border-radius-large);
  padding: 2rem;
  margin-bottom: 2rem;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0 0 1rem 0;
  color: var(--text-primary);
  font-size: 1.1rem;
  font-weight: 600;
}

.section-description {
  color: var(--text-secondary);
  font-size: 0.9rem;
  margin: 0 0 1.5rem 0;
  line-height: 1.5;
}

/* 🆕 Pré-remplissage adresse boutique */
.address-prefill-notice {
  background: rgba(59, 130, 246, 0.1);
  border: 1px solid rgba(59, 130, 246, 0.2);
  border-radius: var(--border-radius);
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.notice-content {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.notice-content .pi {
  color: #3b82f6;
  font-size: 1.25rem;
  margin-top: 0.25rem;
  flex-shrink: 0;
}

.notice-text {
  flex: 1;
}

.notice-text strong {
  color: var(--text-primary);
  display: block;
  margin-bottom: 0.5rem;
}

.notice-text p {
  color: var(--text-secondary);
  margin: 0;
  font-size: 0.9rem;
}

:deep(.emerald-input) {
  width: 100% !important;
  padding: 0.875rem 1rem !important;
  border: 2px solid var(--surface-300) !important;
  border-radius: var(--border-radius) !important;
  background: var(--surface) !important;
  color: var(--text-primary) !important;
  font-size: 0.95rem !important;
  transition: all var(--transition-fast) !important;
}

:deep(.emerald-input:focus) {
  border-color: var(--primary) !important;
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1) !important;
  background: white !important;
}

:deep(.emerald-input.error) {
  border-color: var(--accent) !important;
  box-shadow: 0 0 0 3px rgba(255, 87, 34, 0.1) !important;
}

.field-error {
  color: var(--accent);
  font-size: 0.8rem;
  font-weight: 500;
}

.field-help {
  color: var(--text-secondary);
  font-size: 0.8rem;
  font-style: italic;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--surface-200);
}

/* Role section */
.role-header {
  background: linear-gradient(135deg, var(--secondary), var(--secondary-dark));
  cursor: pointer;
  transition: all var(--transition-fast);
  justify-content: space-between;
}

.role-header:hover {
  background: linear-gradient(135deg, var(--secondary-dark), var(--secondary));
}

.collapse-btn {
  background: none !important;
  border: none !important;
  color: white !important;
  width: auto !important;
  height: auto !important;
  padding: 0.5rem !important;
}

.role-content {
  padding: 2rem;
}

.role-section-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 1.5rem 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.role-section-title::before {
  content: '';
  width: 4px;
  height: 20px;
  background: var(--primary);
  border-radius: 2px;
}

.current-role {
  margin-bottom: 3rem;
}

.current-role-display {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.role-description {
  color: var(--text-secondary);
  font-size: 0.95rem;
  line-height: 1.5;
  margin: 0;
}

.role-upgrade {
  margin-bottom: 3rem;
}

.role-options {
  display: grid;
  gap: 1.5rem;
}

.role-option {
  padding: 1.5rem;
  border: 2px solid var(--surface-200);
  border-radius: var(--border-radius-large);
  background: var(--surface);
  transition: all var(--transition-medium);
}

.role-option:hover {
  border-color: var(--primary);
  background: white;
  box-shadow: var(--shadow-small);
}

.role-option-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.role-benefits {
  font-size: 0.875rem;
  color: var(--text-secondary);
  font-style: italic;
}

.role-option-description {
  color: var(--text-secondary);
  line-height: 1.5;
  margin: 0 0 1.5rem 0;
}

.role-request-btn {
  width: 100%;
}

.role-request-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Pending requests */
.pending-requests {
  background: rgba(38, 166, 154, 0.05);
  border: 1px solid rgba(38, 166, 154, 0.1);
  border-radius: var(--border-radius);
  padding: 1.5rem;
}

.request-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.request-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: white;
  border-radius: var(--border-radius);
  border: 1px solid var(--surface-200);
}

.request-info {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.request-date {
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.request-status {
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.request-status.pending {
  background: rgba(255, 193, 7, 0.1);
  color: #f59e0b;
  border: 1px solid rgba(255, 193, 7, 0.2);
}

.request-status.approved {
  background: rgba(34, 197, 94, 0.1);
  color: #16a34a;
  border: 1px solid rgba(34, 197, 94, 0.2);
}

.request-status.rejected {
  background: rgba(239, 68, 68, 0.1);
  color: #dc2626;
  border: 1px solid rgba(239, 68, 68, 0.2);
}

/* Admin interface */
.admin-role-management {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.admin-requests-section {
  background: rgba(139, 92, 246, 0.05);
  border: 1px solid rgba(139, 92, 246, 0.1);
  border-radius: var(--border-radius);
  padding: 1.5rem;
}

.admin-info {
  color: var(--text-secondary);
  font-size: 0.95rem;
  margin: 0 0 1.5rem 0;
  line-height: 1.5;
}

.empty-admin-requests {
  text-align: center;
  padding: 2rem;
  color: var(--text-secondary);
}

.empty-admin-requests .empty-icon {
  font-size: 2rem;
  margin-bottom: 1rem;
}

.admin-requests-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.admin-request-item {
  background: white;
  border: 1px solid var(--surface-200);
  border-radius: var(--border-radius);
  padding: 1.5rem;
  display: grid;
  grid-template-columns: 1fr 2fr auto;
  gap: 1.5rem;
  align-items: start;
}

.request-user-info h5 {
  margin: 0 0 0.5rem 0;
  color: var(--text-primary);
  font-weight: 600;
}

.user-email {
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.request-details {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.request-message {
  margin: 0;
  color: var(--text-primary);
  line-height: 1.4;
  font-size: 0.9rem;
}

.admin-actions {
  display: flex;
  gap: 0.75rem;
}

.user-role-management {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* Formulaires de demande de rôles inline */
.role-request-form {
  background: white;
  border: 2px solid var(--primary);
  border-radius: var(--border-radius-large);
  padding: 2rem;
  margin-top: 1rem;
}

.form-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--surface-200);
}

.back-btn {
  color: var(--text-secondary) !important;
  padding: 0.5rem !important;
}

.back-btn:hover {
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1) !important;
}

.request-form-content {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.section-subtitle {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0;
  color: var(--text-primary);
  font-size: 1rem;
  font-weight: 600;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--surface-200);
}

/* Sidebar */
.profile-sidebar {
  position: sticky;
  top: 160px;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.sidebar-card {
  background: white;
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-medium);
  border: 1px solid var(--surface-200);
  overflow: hidden;
}

.card-header-custom {
  padding: 1.5rem;
  color: white;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.activity-header {
  background: linear-gradient(135deg, #8b5cf6, #a855f7);
}

.topics-header {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
}

.events-header {
  background: linear-gradient(135deg, var(--accent), var(--accent-dark));
}

.header-icon {
  font-size: 1.25rem;
}

.header-title {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0;
}

/* Activity list */
.activity-list {
  padding: 1.5rem;
  max-height: 300px;
  overflow-y: auto;
}

.activity-item {
  display: flex;
  gap: 1rem;
  padding: 0.75rem 0;
  border-bottom: 1px solid var(--surface-200);
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(139, 92, 246, 0.1);
  color: #8b5cf6;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  flex-shrink: 0;
}

.activity-content {
  flex: 1;
}

.activity-text {
  font-size: 0.875rem;
  color: var(--text-primary);
  margin: 0 0 0.25rem 0;
  line-height: 1.4;
}

.activity-time {
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.empty-activity {
  text-align: center;
  padding: 2rem 0;
}

.empty-icon {
  font-size: 2rem;
  color: var(--text-secondary);
  margin-bottom: 0.75rem;
}

.empty-text {
  color: var(--text-secondary);
  font-size: 0.875rem;
  margin: 0;
}

/* Topics summary */
.topics-summary {
  padding: 1.5rem;
}

.topic-stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.topic-stat {
  text-align: center;
  padding: 1rem;
  background: var(--surface-100);
  border-radius: var(--border-radius);
}

.topic-stat .stat-value {
  display: block;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--primary);
  line-height: 1;
}

.topic-stat .stat-label {
  display: block;
  font-size: 0.75rem;
  color: var(--text-secondary);
  margin-top: 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Events summary */
.events-summary {
  padding: 1.5rem;
}

.event-stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.event-stat {
  text-align: center;
  padding: 1rem;
  background: rgba(255, 87, 34, 0.05);
  border-radius: var(--border-radius);
  border: 1px solid rgba(255, 87, 34, 0.1);
}

.event-stat .stat-value {
  display: block;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--accent);
  line-height: 1;
}

.event-stat .stat-label {
  display: block;
  font-size: 0.75rem;
  color: var(--text-secondary);
  margin-top: 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.event-actions {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

/* Buttons */
:deep(.emerald-btn) {
  background: var(--emerald-gradient) !important;
  border: none !important;
  color: white !important;
  font-weight: 600 !important;
  padding: 0.75rem 1.5rem !important;
  border-radius: var(--border-radius) !important;
  transition: all var(--transition-fast) !important;
}

:deep(.emerald-btn:hover) {
  background: linear-gradient(135deg, var(--primary-dark), var(--primary)) !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 12px rgba(38, 166, 154, 0.3) !important;
}

:deep(.emerald-outline-btn) {
  background: none !important;
  border: 2px solid var(--primary) !important;
  color: var(--primary) !important;
  font-weight: 500 !important;
  padding: 0.75rem 1.5rem !important;
  border-radius: var(--border-radius) !important;
  transition: all var(--transition-fast) !important;
}

:deep(.emerald-outline-btn:hover) {
  background: var(--primary) !important;
  color: white !important;
  transform: translateY(-1px) !important;
}

:deep(.emerald-outline-btn.small) {
  padding: 0.5rem 1rem !important;
  font-size: 0.875rem !important;
}

:deep(.emerald-btn.small) {
  padding: 0.5rem 1rem !important;
  font-size: 0.875rem !important;
}

/* Modal overrides */
:deep(.emerald-modal .p-dialog) {
  border-radius: var(--border-radius-large) !important;
  box-shadow: var(--shadow-large) !important;
  border: 1px solid var(--surface-200) !important;
}

:deep(.emerald-modal .p-dialog-header) {
  background: var(--emerald-gradient) !important;
  color: white !important;
  padding: 1.5rem 2rem !important;
  border-bottom: none !important;
}

:deep(.emerald-modal .p-dialog-content) {
  padding: 2rem !important;
  background: var(--surface) !important;
}

:deep(.emerald-modal .p-dialog-footer) {
  padding: 1.5rem 2rem !important;
  background: var(--surface) !important;
  border-top: 1px solid var(--surface-200) !important;
  display: flex !important;
  justify-content: flex-end !important;
  gap: 1rem !important;
}

/* Modal de confirmation */
.confirm-content {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.confirm-icon {
  font-size: 2rem;
  color: #f59e0b;
  flex-shrink: 0;
  margin-top: 0.25rem;
}

.confirm-text p {
  margin: 0 0 1rem 0;
  line-height: 1.5;
}

.confirm-text p:last-child {
  margin-bottom: 0;
  color: var(--text-secondary);
  font-size: 0.95rem;
}

/* Responsive */
@media (max-width: 1024px) {
  .profile-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .profile-sidebar {
    position: static;
    grid-row: 2;
  }
  
  .profile-sidebar .sidebar-card {
    display: grid;
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .container {
    padding: 0 1rem;
  }
  
  .profile-page {
    padding: 1rem 0;
  }
  
  .profile-header-content {
    flex-direction: column;
    gap: 1.5rem;
    padding: 1.5rem;
  }
  
  .avatar-section {
    flex-direction: column;
    text-align: center;
    gap: 1.5rem;
  }
  
  .profile-stats {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .quick-actions {
    justify-content: center;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .role-option-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .admin-request-item {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .admin-actions {
    justify-content: flex-start;
  }
}

@media (max-width: 640px) {
  :deep(.profile-avatar) {
    width: 80px !important;
    height: 80px !important;
    font-size: 2rem !important;
  }
  
  .username {
    font-size: 1.5rem;
  }
  
  .profile-stats {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .role-badges {
    justify-content: center;
  }
  
  .form-actions {
    flex-direction: column-reverse;
  }
  
  .event-actions {
    gap: 0.5rem;
  }
  
  .notice-content {
    flex-direction: column;
    gap: 1rem;
  }
  
  .address-section {
    padding: 1.5rem;
  }
}

/* Admin requests enhanced */
.admin-request-card {
  background: white;
  border: 2px solid var(--surface-200);
  border-radius: var(--border-radius-large);
  margin-bottom: 1rem;
  transition: all var(--transition-medium);
  overflow: hidden;
}

.admin-request-card:hover {
  border-color: var(--primary);
  box-shadow: var(--shadow-medium);
}

.admin-request-card.expanded {
  border-color: var(--primary);
  box-shadow: var(--shadow-large);
}

.request-header {
  padding: 1.5rem;
  display: grid;
  grid-template-columns: 1fr 1fr auto auto;
  gap: 1rem;
  align-items: center;
  cursor: pointer;
  transition: background var(--transition-fast);
}

.request-header:hover {
  background: rgba(38, 166, 154, 0.02);
}

.request-user-info h5 {
  margin: 0 0 0.25rem 0;
  color: var(--text-primary);
  font-weight: 600;
  font-size: 1rem;
}

.user-email {
  font-size: 0.8rem;
  color: var(--text-secondary);
}

.request-summary {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.request-date {
  font-size: 0.8rem;
  color: var(--text-secondary);
}

.confidence-score {
  display: flex;
  justify-content: flex-end;
}

.score-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.score-badge {
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 700;
  font-size: 0.9rem;
  text-align: center;
  min-width: 70px;
}

.score-badge.score-high {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
}

.score-badge.score-medium {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white;
}

.score-badge.score-low {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: white;
}

.score-badge.score-very-low {
  background: linear-gradient(135deg, #991b1b, #7f1d1d);
  color: white;
}

.confidence-level {
  font-size: 0.7rem;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.expand-icon {
  color: var(--text-secondary);
  font-size: 1.2rem;
  transition: transform var(--transition-fast);
}

.admin-request-card.expanded .expand-icon {
  transform: rotate(180deg);
}

/* Détails expandables */
.request-details-expanded {
  border-top: 1px solid var(--surface-200);
  padding: 2rem;
  background: rgba(248, 250, 252, 0.5);
  animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    max-height: 0;
  }
  to {
    opacity: 1;
    max-height: 1000px;
  }
}

.request-details-expanded h6 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 1rem 0;
  color: var(--text-primary);
  font-weight: 600;
  font-size: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--surface-300);
}

.basic-details {
  margin-bottom: 2rem;
}

.request-message {
  background: white;
  padding: 1rem;
  border-radius: var(--border-radius);
  border-left: 4px solid var(--primary);
  margin: 0;
  line-height: 1.5;
  font-style: italic;
}

/* Shop details */
.shop-details {
  margin-bottom: 2rem;
}

.shop-info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-item.full-width {
  grid-column: 1 / -1;
}

.info-item label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-item span {
  color: var(--text-primary);
  font-weight: 500;
}

.siret-number {
  font-family: 'Courier New', monospace;
  background: rgba(38, 166, 154, 0.1);
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  display: inline-block;
}

.website-link {
  color: var(--primary);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: color var(--transition-fast);
}

.website-link:hover {
  color: var(--primary-dark);
  text-decoration: underline;
}

/* Verification details */
.verification-details {
  margin-bottom: 2rem;
}

.verification-grid {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 2rem;
  margin-bottom: 1.5rem;
}

.verification-score-detail {
  display: flex;
  justify-content: center;
}

.score-display {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.score-circle {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  position: relative;
  border: 4px solid;
}

.score-circle.score-high {
  border-color: #10b981;
  background: rgba(16, 185, 129, 0.1);
}

.score-circle.score-medium {
  border-color: #f59e0b;
  background: rgba(245, 158, 11, 0.1);
}

.score-circle.score-low {
  border-color: #ef4444;
  background: rgba(239, 68, 68, 0.1);
}

.score-circle.score-very-low {
  border-color: #991b1b;
  background: rgba(153, 27, 27, 0.1);
}

.score-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1;
}

.score-max {
  font-size: 0.8rem;
  color: var(--text-secondary);
  line-height: 1;
}

.score-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.confidence-text {
  font-weight: 600;
  color: var(--text-primary);
}

.verification-date {
  font-size: 0.8rem;
  color: var(--text-secondary);
}

.verification-statuses {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.status-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: white;
  border-radius: var(--border-radius);
  border: 1px solid var(--surface-200);
}

.status-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: var(--text-primary);
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-badge.status-success {
  background: rgba(34, 197, 94, 0.1);
  color: #16a34a;
  border: 1px solid rgba(34, 197, 94, 0.2);
}

.status-badge.status-warning {
  background: rgba(245, 158, 11, 0.1);
  color: #d97706;
  border: 1px solid rgba(245, 158, 11, 0.2);
}

.status-badge.status-error {
  background: rgba(239, 68, 68, 0.1);
  color: #dc2626;
  border: 1px solid rgba(239, 68, 68, 0.2);
}

/* Recommendations */
.recommendations {
  margin-bottom: 1.5rem;
}

.recommendation-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.recommendation-list li {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.75rem;
  background: rgba(59, 130, 246, 0.05);
  border-left: 3px solid #3b82f6;
  border-radius: var(--border-radius);
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  line-height: 1.4;
}

.recommendation-list li .pi {
  color: #3b82f6;
  margin-top: 0.1rem;
  flex-shrink: 0;
}

/* Google Maps */
.google-places-embed {
  margin-bottom: 1.5rem;
}

.maps-embed-container {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  align-items: flex-start;
}

.maps-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.maps-note {
  font-size: 0.75rem;
  color: var(--text-secondary);
  font-family: 'Courier New', monospace;
}

/* Actions admin expandues */
.admin-actions-expanded {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1.5rem;
  border-top: 1px solid var(--surface-300);
}

.action-group {
  display: flex;
  gap: 1rem;
}

.action-secondary {
  display: flex;
  gap: 0.5rem;
}

/* Responsive */
@media (max-width: 1024px) {
  .request-header {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .confidence-score {
    justify-content: flex-start;
  }
  
  .verification-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .shop-info-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .request-details-expanded {
    padding: 1rem;
  }
  
  .admin-actions-expanded {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .action-group {
    width: 100%;
  }
  
  .action-group .emerald-btn,
  .action-group .p-button {
    flex: 1;
  }
}

/* Activity list - MISE À JOUR */
.activity-list {
  padding: 1.5rem;
  max-height: 400px; /* Augmenté pour plus d'éléments */
  overflow-y: auto;
}

.activity-item {
  display: flex;
  gap: 1rem;
  padding: 0.75rem 0;
  border-bottom: 1px solid var(--surface-200);
  cursor: pointer;
  transition: all var(--transition-fast);
  position: relative;
}

.activity-item:hover {
  background: rgba(38, 166, 154, 0.05);
  border-radius: var(--border-radius);
  margin: 0 -0.5rem;
  padding: 0.75rem 0.5rem;
}

/* 🆕 Notification non lue */
.activity-item.activity-unread {
  background: rgba(38, 166, 154, 0.05);
  border-left: 3px solid var(--primary);
  padding-left: 1rem;
  margin-left: -1rem;
}

/* 🆕 Icône pour notification non lue */
.activity-icon.unread-icon {
  background: rgba(38, 166, 154, 0.15);
  border: 2px solid var(--primary);
  animation: pulse 2s infinite;
}

/* 🆕 Emoji des notifications */
.notification-emoji {
  font-size: 1.2rem;
  line-height: 1;
}

/* 🆕 Indicateur non lu */
.unread-indicator {
  width: 8px;
  height: 8px;
  background: var(--primary);
  border-radius: 50%;
  flex-shrink: 0;
  margin-top: 0.5rem;
}

/* 🆕 État de chargement */
.loading-activity {
  text-align: center;
  padding: 2rem 0;
  color: var(--text-secondary);
}

.loading-activity .pi-spinner {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
}

/* 🆕 Section charger plus */
.load-more-section {
  text-align: center;
  padding: 1rem 0;
  border-top: 1px solid var(--surface-200);
  margin-top: 1rem;
}

:deep(.load-more-btn) {
  color: var(--primary) !important;
  font-weight: 500 !important;
  padding: 0.75rem 1.5rem !important;
  border-radius: var(--border-radius) !important;
  transition: all var(--transition-fast) !important;
}

:deep(.load-more-btn:hover) {
  background: rgba(38, 166, 154, 0.1) !important;
  transform: translateY(-1px) !important;
}
</style>