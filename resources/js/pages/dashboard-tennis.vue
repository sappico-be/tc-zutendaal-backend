<template>
  <VContainer>
    <VRow>
      <VCol cols="12">
        <h1 class="text-h4 mb-6">Tennis Club Dashboard</h1>
      </VCol>
    </VRow>
    
    <!-- Statistics Cards -->
    <VRow>
      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <p class="text-body-2 text-uppercase mb-1">Totaal Leden</p>
              <h3 class="text-h4">{{ stats.totalMembers }}</h3>
              <p class="text-body-2">{{ stats.activeMembers }} actief</p>
            </div>
            <VIcon icon="tabler-users" size="40" color="primary" />
          </VCardText>
        </VCard>
      </VCol>
      
      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <p class="text-body-2 text-uppercase mb-1">Events</p>
              <h3 class="text-h4">{{ stats.upcomingEvents }}</h3>
              <p class="text-body-2">Deze maand</p>
            </div>
            <VIcon icon="tabler-calendar" size="40" color="success" />
          </VCardText>
        </VCard>
      </VCol>
      
      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <p class="text-body-2 text-uppercase mb-1">Inschrijvingen</p>
              <h3 class="text-h4">{{ stats.recentRegistrations }}</h3>
              <p class="text-body-2">Deze week</p>
            </div>
            <VIcon icon="tabler-user-check" size="40" color="warning" />
          </VCardText>
        </VCard>
      </VCol>
      
      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <p class="text-body-2 text-uppercase mb-1">Omzet</p>
              <h3 class="text-h4">€{{ formatCurrency(stats.monthlyRevenue) }}</h3>
              <p class="text-body-2">Deze maand</p>
            </div>
            <VIcon icon="tabler-currency-euro" size="40" color="info" />
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
    
    <!-- Quick Actions -->
    <VRow class="mt-6">
      <VCol cols="12">
        <VCard>
          <VCardItem>
            <VCardTitle>Snelle Acties</VCardTitle>
          </VCardItem>
          <VCardText>
            <VBtn color="primary" class="me-3" prepend-icon="tabler-news">
              Nieuws Toevoegen
            </VBtn>
            <VBtn color="success" class="me-3" prepend-icon="tabler-calendar-plus">
              Event Aanmaken
            </VBtn>
            <VBtn color="info" prepend-icon="tabler-user-plus">
              Lid Toevoegen
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
    
    <!-- Recent Activity -->
    <VRow class="mt-6">
      <VCol cols="12" md="6">
        <VCard>
          <VCardItem>
            <VCardTitle>Recent Nieuws</VCardTitle>
          </VCardItem>
          <VCardText>
            <VList>
              <VListItem v-for="article in stats.latestNews" :key="article.id">
                <VListItemTitle>{{ article.title }}</VListItemTitle>
                <VListItemSubtitle>
                  {{ formatDate(article.published_at) }} - {{ article.views }} views
                </VListItemSubtitle>
              </VListItem>
            </VList>
          </VCardText>
        </VCard>
      </VCol>
      
      <VCol cols="12" md="6">
        <VCard>
          <VCardItem>
            <VCardTitle>Komende Events</VCardTitle>
          </VCardItem>
          <VCardText>
            <VList>
              <VListItem v-for="event in stats.upcomingEventsList" :key="event.id">
                <VListItemTitle>{{ event.title }}</VListItemTitle>
                <VListItemSubtitle>
                  {{ formatDate(event.start_date) }} - {{ event.confirmed_registrations_count || 0 }} inschrijvingen
                </VListItemSubtitle>
              </VListItem>
            </VList>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/plugins/axios'

// Page meta
definePage({
  meta: {
    requiresAuth: true,
  },
})

// Data
const stats = ref({
  totalMembers: 0,
  activeMembers: 0,
  upcomingEvents: 0,
  recentRegistrations: 0,
  monthlyRevenue: 0,
  latestNews: [],
  upcomingEventsList: [],
})

// Load dashboard data
const loadDashboardData = async () => {
  try {
    const response = await axios.get('/dashboard/stats')
    stats.value = response.data
  } catch (error) {
    console.error('Failed to load dashboard data:', error)
    // Use dummy data for testing
    stats.value = {
      totalMembers: 127,
      activeMembers: 98,
      upcomingEvents: 5,
      recentRegistrations: 12,
      monthlyRevenue: 3250.00,
      latestNews: [
        { id: 1, title: 'Clubkampioenschap 2025', published_at: new Date(), views: 45 },
        { id: 2, title: 'Nieuwe trainingstijden', published_at: new Date(), views: 32 },
      ],
      upcomingEventsList: [
        { id: 1, title: 'Zomertoernooi', start_date: new Date(), confirmed_registrations_count: 24 },
        { id: 2, title: 'Training beginners', start_date: new Date(), confirmed_registrations_count: 8 },
      ],
    }
  }
}

// Formatters
const formatCurrency = (value) => {
  return new Intl.NumberFormat('nl-BE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value || 0)
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('nl-BE')
}

// Load data on mount
onMounted(() => {
  loadDashboardData()
})
</script>
