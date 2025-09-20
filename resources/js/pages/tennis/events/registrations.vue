<template>
  <VContainer>
    <!-- Page Header -->
    <VRow>
      <VCol cols="12">
        <div class="d-flex justify-space-between align-center mb-6">
          <div>
            <h1 class="text-h4">Inschrijvingen</h1>
            <p class="text-body-1 mt-1" v-if="event">{{ event.title }}</p>
          </div>
          <VBtn 
            variant="outlined"
            :to="{ name: 'tennis-events-list' }"
          >
            Terug
          </VBtn>
            <VBtn 
                color="primary"
                @click="showAddDialog = true"
                class="ml-2"
                >
                Handmatige Inschrijving
            </VBtn>
        </div>
      </VCol>
    </VRow>

    <!-- Statistics Cards -->
    <VRow>
      <VCol cols="12" sm="3">
        <VCard>
          <VCardText class="text-center">
            <div class="text-h4">{{ stats.total }}</div>
            <div class="text-body-2">Totaal</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" sm="3">
        <VCard>
          <VCardText class="text-center">
            <div class="text-h4 text-success">{{ stats.confirmed }}</div>
            <div class="text-body-2">Bevestigd</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" sm="3">
        <VCard>
          <VCardText class="text-center">
            <div class="text-h4 text-warning">{{ stats.pending }}</div>
            <div class="text-body-2">In afwachting</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" sm="3">
        <VCard>
          <VCardText class="text-center">
            <div class="text-h4 text-info">€{{ stats.revenue }}</div>
            <div class="text-body-2">Opbrengst</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Registrations Table -->
    <VRow class="mt-6">
      <VCol cols="12">
        <VCard>
          <VCardText>
            <VDataTable
              :headers="headers"
              :items="registrations"
              :loading="loading"
            >
              <template #item.user="{ item }">
                {{ item.user?.name || '-' }}
              </template>
              
              <template #item.status="{ item }">
                <VChip
                  :color="getStatusColor(item.status)"
                  size="small"
                >
                  {{ item.status }}
                </VChip>
              </template>
              
              <template #item.payment_status="{ item }">
                <VChip
                  :color="getPaymentColor(item.payment_status)"
                  size="small"
                >
                  {{ item.payment_status }}
                </VChip>
              </template>
              
              <template #item.amount_paid="{ item }">
                €{{ item.amount_paid }}
              </template>
              
              <template #item.created_at="{ item }">
                {{ formatDate(item.created_at) }}
              </template>
              
              <template #item.actions="{ item }">
                <VBtn
                  v-if="item.status !== 'confirmed'"
                  size="small"
                  color="success"
                  variant="text"
                  @click="updateStatus(item.id, 'confirmed')"
                >
                  Bevestig
                </VBtn>
                <VBtn
                  v-if="item.payment_status !== 'paid'"
                  size="small"
                  color="info"
                  variant="text"
                  @click="markAsPaid(item.id)"
                >
                  Betaald
                </VBtn>
              </template>
            </VDataTable>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
  <!-- Add Registration Dialog -->
<VDialog v-model="showAddDialog" max-width="500">
  <VCard>
    <VCardTitle>Nieuwe Inschrijving</VCardTitle>
    <VCardText>
      <VTextField
        v-model="newRegistration.name"
        label="Naam"
        class="mb-3"
      />
      <VTextField
        v-model="newRegistration.email"
        label="Email"
        type="email"
        class="mb-3"
      />
      <VTextField
        v-model="newRegistration.phone"
        label="Telefoon"
      />
    </VCardText>
    <VCardActions>
      <VSpacer />
      <VBtn @click="showAddDialog = false">Annuleer</VBtn>
      <VBtn color="primary" @click="createPayment">Start Betaling</VBtn>
    </VCardActions>
  </VCard>
</VDialog>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from '@/plugins/axios'

definePage({
  meta: {
    requiresAuth: true,
  },
})

const route = useRoute()
const loading = ref(false)
const event = ref(null)
const registrations = ref([])
const stats = ref({
  total: 0,
  confirmed: 0,
  pending: 0,
  revenue: 0,
})

const headers = [
  { title: 'Naam', key: 'user' },
  { title: 'Email', value: 'user.email' },
  { title: 'Status', key: 'status' },
  { title: 'Betaling', key: 'payment_status' },
  { title: 'Bedrag', key: 'amount_paid' },
  { title: 'Datum', key: 'created_at' },
  { title: 'Acties', key: 'actions', sortable: false },
]

const loadRegistrations = async () => {
  loading.value = true
  try {
    // Load event details
    const eventResponse = await axios.get(`/events/${route.params.id}`)
    event.value = eventResponse.data.data
    
    // Load registrations
    const response = await axios.get(`/events/${route.params.id}/registrations`)
    registrations.value = response.data.data
    stats.value = response.data.summary || {
      total: registrations.value.length,
      confirmed: registrations.value.filter(r => r.status === 'confirmed').length,
      pending: registrations.value.filter(r => r.status === 'pending').length,
      revenue: registrations.value.reduce((sum, r) => sum + parseFloat(r.amount_paid || 0), 0).toFixed(2),
    }
  } catch (error) {
    console.error('Error loading registrations:', error)
  } finally {
    loading.value = false
  }
}

const updateStatus = async (registrationId, status) => {
  try {
    await axios.patch(`/events/${route.params.id}/registrations/${registrationId}`, {
      status: status
    })
    await loadRegistrations()
  } catch (error) {
    console.error('Error updating status:', error)
  }
}

const markAsPaid = async (registrationId) => {
  try {
    await axios.patch(`/events/${route.params.id}/registrations/${registrationId}`, {
      payment_status: 'paid',
      amount_paid: event.value?.price_members || 0
    })
    await loadRegistrations()
  } catch (error) {
    console.error('Error marking as paid:', error)
  }
}

const getStatusColor = (status) => {
  const colors = {
    pending: 'warning',
    confirmed: 'success',
    cancelled: 'error',
    waitlist: 'info',
  }
  return colors[status] || 'default'
}

const getPaymentColor = (status) => {
  const colors = {
    unpaid: 'error',
    paid: 'success',
    refunded: 'warning',
  }
  return colors[status] || 'default'
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('nl-BE')
}

const showAddDialog = ref(false)
const newRegistration = ref({
  name: '',
  email: '',
  phone: ''
})

const createPayment = async () => {
  try {
    const response = await axios.post(`/events/${route.params.id}/register-and-pay`, newRegistration.value)
    
    if (response.data.payment_url) {
      window.location.href = response.data.payment_url
    } else {
      showAddDialog.value = false
      await loadRegistrations()
    }
  } catch (error) {
    alert(error.response?.data?.error || 'Er ging iets mis')
  }
}

onMounted(() => {
  loadRegistrations()
})
</script>
