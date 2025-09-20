<template>
  <VContainer>
    <!-- Header -->
    <VRow>
      <VCol cols="12">
        <div class="d-flex justify-space-between align-center mb-6">
          <div>
            <h1 class="text-h4">Financieel Overzicht</h1>
            <p class="text-body-1 mt-1" v-if="lessonPackage">{{ lessonPackage.name }}</p>
          </div>
          <div>
            <VBtn 
              variant="outlined"
              :to="{ name: 'tennis-lessons-packages' }"
              class="mr-2"
            >
              Terug
            </VBtn>
            <VBtn 
              color="primary"
              prepend-icon="tabler-download"
              @click="exportReport"
            >
              Export PDF
            </VBtn>
          </div>
        </div>
      </VCol>
    </VRow>

    <!-- Key Metrics -->
    <VRow>
      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-body-2 text-disabled">Verwachte Omzet</div>
                <div class="text-h4 mt-1">€{{ formatAmount(expectedRevenue) }}</div>
              </div>
              <VIcon size="40" color="primary" icon="tabler-report-money" />
            </div>
          </VCardText>
        </VCard>
      </VCol>
      
      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-body-2 text-disabled">Ontvangen</div>
                <div class="text-h4 mt-1 text-success">€{{ formatAmount(actualRevenue) }}</div>
              </div>
              <VIcon size="40" color="success" icon="tabler-cash" />
            </div>
            <VProgressLinear
              :model-value="collectionRate"
              color="success"
              height="4"
              class="mt-3"
            />
            <div class="text-caption mt-1">{{ collectionRate }}% geïnd</div>
          </VCardText>
        </VCard>
      </VCol>
      
      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-body-2 text-disabled">Openstaand</div>
                <div class="text-h4 mt-1 text-warning">€{{ formatAmount(outstanding) }}</div>
              </div>
              <VIcon size="40" color="warning" icon="tabler-clock-dollar" />
            </div>
            <div class="text-caption mt-3">{{ unpaidCount }} onbetaalde inschrijvingen</div>
          </VCardText>
        </VCard>
      </VCol>
      
      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-body-2 text-disabled">Gem. per deelnemer</div>
                <div class="text-h4 mt-1">€{{ formatAmount(averagePerParticipant) }}</div>
              </div>
              <VIcon size="40" color="info" icon="tabler-users" />
            </div>
            <div class="text-caption mt-3">{{ totalRegistrations }} inschrijvingen</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Charts Row -->
    <VRow class="mt-6">
      <!-- Payment Status Distribution -->
      <VCol cols="12" md="6">
        <VCard>
          <VCardItem>
            <VCardTitle>Betaalstatus Verdeling</VCardTitle>
          </VCardItem>
          <VCardText>
            <canvas ref="paymentStatusChart" height="300"></canvas>
            
            <VList class="mt-4">
              <VListItem v-for="(value, status) in paymentStatusBreakdown" :key="status">
                <template #prepend>
                  <VIcon :color="getPaymentStatusColor(status)" size="12">
                    tabler-circle-filled
                  </VIcon>
                </template>
                <VListItemTitle>{{ getPaymentStatusLabel(status) }}</VListItemTitle>
                <template #append>
                  <div class="text-right">
                    <div>{{ value.count }} ({{ value.percentage }}%)</div>
                    <div class="text-caption text-disabled">€{{ formatAmount(value.amount) }}</div>
                  </div>
                </template>
              </VListItem>
            </VList>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Member Type Revenue -->
      <VCol cols="12" md="6">
        <VCard>
          <VCardItem>
            <VCardTitle>Omzet per Lidmaatschap Type</VCardTitle>
          </VCardItem>
          <VCardText>
            <canvas ref="memberTypeChart" height="300"></canvas>
            
            <VTable class="mt-4">
              <thead>
                <tr>
                  <th>Type</th>
                  <th class="text-center">Aantal</th>
                  <th class="text-right">Tarief</th>
                  <th class="text-right">Totaal</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(data, type) in memberTypeBreakdown" :key="type">
                  <td>{{ getMemberTypeLabel(type) }}</td>
                  <td class="text-center">{{ data.count }}</td>
                  <td class="text-right">€{{ formatAmount(data.rate) }}</td>
                  <td class="text-right font-weight-bold">€{{ formatAmount(data.total) }}</td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Group Financial Overview -->
    <VRow class="mt-6">
      <VCol cols="12">
        <VCard>
          <VCardItem>
            <VCardTitle>Financieel Overzicht per Groep</VCardTitle>
          </VCardItem>
          <VCardText>
            <VDataTable
              :headers="groupHeaders"
              :items="groupFinancials"
              :loading="loadingGroups"
            >
              <template #item.group_name="{ item }">
                <div class="font-weight-bold">{{ item.group_name }}</div>
                <div class="text-caption text-disabled">{{ item.trainer_name || 'Geen trainer' }}</div>
              </template>
              
              <template #item.participants="{ item }">
                <VChip size="small">
                  {{ item.participants }} / {{ item.max_participants }}
                </VChip>
              </template>
              
              <template #item.fill_rate="{ item }">
                <div class="d-flex align-center">
                  <VProgressLinear
                    :model-value="item.fill_rate"
                    :color="getFillRateColor(item.fill_rate)"
                    height="20"
                    class="me-3"
                    style="min-width: 80px"
                  >
                    {{ item.fill_rate }}%
                  </VProgressLinear>
                </div>
              </template>
              
              <template #item.expected_revenue="{ item }">
                €{{ formatAmount(item.expected_revenue) }}
              </template>
              
              <template #item.collected_revenue="{ item }">
                <div>
                  <span class="text-success">€{{ formatAmount(item.collected_revenue) }}</span>
                  <div class="text-caption text-disabled">
                    {{ item.collection_rate }}% geïnd
                  </div>
                </div>
              </template>
              
              <template #item.outstanding="{ item }">
                <span :class="item.outstanding > 0 ? 'text-warning' : 'text-success'">
                  €{{ formatAmount(item.outstanding) }}
                </span>
              </template>
            </VDataTable>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Detailed Payment List -->
    <VRow class="mt-6">
      <VCol cols="12">
        <VCard>
          <VCardItem>
            <VCardTitle>Gedetailleerde Betalingslijst</VCardTitle>
            <template #append>
              <VBtn
                size="small"
                variant="outlined"
                @click="sendPaymentReminders"
                prepend-icon="tabler-mail"
                :loading="sendingReminders"
              >
                Stuur Herinneringen ({{ unpaidCount }})
              </VBtn>
            </template>
          </VCardItem>
          <VCardText>
            <!-- Filters -->
            <VRow class="mb-4">
              <VCol cols="12" md="4">
                <VSelect
                  v-model="filterPaymentStatus"
                  label="Filter op status"
                  :items="paymentStatusOptions"
                  clearable
                  density="compact"
                />
              </VCol>
              <VCol cols="12" md="4">
                <VSelect
                  v-model="filterGroup"
                  label="Filter op groep"
                  :items="groupOptions"
                  item-title="name"
                  item-value="id"
                  clearable
                  density="compact"
                />
              </VCol>
              <VCol cols="12" md="4">
                <VTextField
                  v-model="searchQuery"
                  label="Zoek op naam/email"
                  prepend-inner-icon="tabler-search"
                  clearable
                  density="compact"
                />
              </VCol>
            </VRow>

            <VDataTable
              :headers="paymentHeaders"
              :items="filteredRegistrations"
              :loading="loadingRegistrations"
              :search="searchQuery"
            >
              <template #item.user="{ item }">
                <div>
                  <div class="font-weight-bold">{{ item.user.name }}</div>
                  <div class="text-caption text-disabled">{{ item.user.email }}</div>
                </div>
              </template>
              
              <template #item.group="{ item }">
                {{ item.assignedGroup?.name || '-' }}
              </template>
              
              <template #item.membership_type="{ item }">
                <VChip size="small" :color="getMembershipColor(item.user.membership_type)">
                  {{ getMemberTypeLabel(item.user.membership_type) }}
                </VChip>
              </template>
              
              <template #item.amount="{ item }">
                €{{ formatAmount(item.amount) }}
              </template>
              
              <template #item.payment_status="{ item }">
                <VChip 
                  size="small" 
                  :color="getPaymentStatusColor(item.payment_status)"
                >
                  {{ getPaymentStatusLabel(item.payment_status) }}
                </VChip>
              </template>
              
              <template #item.paid_at="{ item }">
                {{ item.paid_at ? formatDate(item.paid_at) : '-' }}
              </template>
              
              <template #item.actions="{ item }">
                <VBtn
                  v-if="item.payment_status === 'unpaid'"
                  size="small"
                  variant="text"
                  color="primary"
                  @click="markAsPaid(item)"
                >
                  Markeer betaald
                </VBtn>
                <VBtn
                  v-if="item.payment_status === 'unpaid'"
                  size="small"
                  variant="text"
                  color="warning"
                  @click="sendIndividualReminder(item)"
                  icon="tabler-mail"
                />
              </template>
            </VDataTable>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Summary Statistics -->
    <VRow class="mt-6">
      <VCol cols="12">
        <VCard>
          <VCardItem>
            <VCardTitle>Samenvatting & Prognose</VCardTitle>
          </VCardItem>
          <VCardText>
            <VRow>
              <VCol cols="12" md="6">
                <h4 class="mb-3">Huidige Status</h4>
                <VList>
                  <VListItem>
                    <VListItemTitle>Totaal aantal inschrijvingen</VListItemTitle>
                    <template #append>
                      <VListItemTitle class="font-weight-bold">{{ totalRegistrations }}</VListItemTitle>
                    </template>
                  </VListItem>
                  <VListItem>
                    <VListItemTitle>Gemiddelde groepsbezetting</VListItemTitle>
                    <template #append>
                      <VListItemTitle class="font-weight-bold">{{ averageFillRate }}%</VListItemTitle>
                    </template>
                  </VListItem>
                  <VListItem>
                    <VListItemTitle>Totaal gefactureerd</VListItemTitle>
                    <template #append>
                      <VListItemTitle class="font-weight-bold">€{{ formatAmount(expectedRevenue) }}</VListItemTitle>
                    </template>
                  </VListItem>
                  <VListItem>
                    <VListItemTitle>Totaal ontvangen</VListItemTitle>
                    <template #append>
                      <VListItemTitle class="font-weight-bold text-success">€{{ formatAmount(actualRevenue) }}</VListItemTitle>
                    </template>
                  </VListItem>
                  <VListItem>
                    <VListItemTitle>Nog te ontvangen</VListItemTitle>
                    <template #append>
                      <VListItemTitle class="font-weight-bold text-warning">€{{ formatAmount(outstanding) }}</VListItemTitle>
                    </template>
                  </VListItem>
                </VList>
              </VCol>
              
              <VCol cols="12" md="6">
                <h4 class="mb-3">Prognose bij volledige bezetting</h4>
                <VList>
                  <VListItem>
                    <VListItemTitle>Maximale capaciteit</VListItemTitle>
                    <template #append>
                      <VListItemTitle class="font-weight-bold">{{ maxCapacity }} deelnemers</VListItemTitle>
                    </template>
                  </VListItem>
                  <VListItem>
                    <VListItemTitle>Potentiële omzet (100% bezetting)</VListItemTitle>
                    <template #append>
                      <VListItemTitle class="font-weight-bold">€{{ formatAmount(potentialRevenue) }}</VListItemTitle>
                    </template>
                  </VListItem>
                  <VListItem>
                    <VListItemTitle>Gemiste omzet door lege plekken</VListItemTitle>
                    <template #append>
                      <VListItemTitle class="font-weight-bold text-error">€{{ formatAmount(missedRevenue) }}</VListItemTitle>
                    </template>
                  </VListItem>
                  <VListItem>
                    <VListItemTitle>Break-even punt (min. deelnemers)</VListItemTitle>
                    <template #append>
                      <VListItemTitle class="font-weight-bold">{{ lessonPackage?.min_participants || 'N/B' }}</VListItemTitle>
                    </template>
                  </VListItem>
                </VList>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from '@/plugins/axios'

definePage({
  meta: {
    requiresAuth: true,
  },
})

const route = useRoute()
const packageId = route.params.id

// Data refs
const lessonPackage = ref(null)
const registrations = ref([])
const groups = ref([])
const loadingGroups = ref(false)
const loadingRegistrations = ref(false)
const sendingReminders = ref(false)

// Filter refs
const filterPaymentStatus = ref(null)
const filterGroup = ref(null)
const searchQuery = ref('')

// Chart refs
const paymentStatusChart = ref(null)
const memberTypeChart = ref(null)

// Computed: Key Metrics
const totalRegistrations = computed(() => registrations.value.length)

const expectedRevenue = computed(() => {
  return registrations.value.reduce((total, reg) => {
    return total + (reg.amount || 0)
  }, 0)
})

const actualRevenue = computed(() => {
  return registrations.value
    .filter(reg => reg.payment_status === 'paid')
    .reduce((total, reg) => total + (reg.amount_paid || 0), 0)
})

const outstanding = computed(() => expectedRevenue.value - actualRevenue.value)

const collectionRate = computed(() => {
  if (expectedRevenue.value === 0) return 0
  return Math.round((actualRevenue.value / expectedRevenue.value) * 100)
})

const unpaidCount = computed(() => {
  return registrations.value.filter(reg => reg.payment_status === 'unpaid').length
})

const averagePerParticipant = computed(() => {
  if (totalRegistrations.value === 0) return 0
  return Math.round(expectedRevenue.value / totalRegistrations.value)
})

// Computed: Payment Status Breakdown
const paymentStatusBreakdown = computed(() => {
  const breakdown = {}
  const total = registrations.value.length
  
  registrations.value.forEach(reg => {
    if (!breakdown[reg.payment_status]) {
      breakdown[reg.payment_status] = { count: 0, amount: 0, percentage: 0 }
    }
    breakdown[reg.payment_status].count++
    breakdown[reg.payment_status].amount += reg.amount || 0
  })
  
  Object.keys(breakdown).forEach(status => {
    breakdown[status].percentage = Math.round((breakdown[status].count / total) * 100)
  })
  
  return breakdown
})

// Computed: Member Type Breakdown
const memberTypeBreakdown = computed(() => {
  const breakdown = {}
  
  registrations.value.forEach(reg => {
    const type = reg.user.membership_type || 'non_member'
    if (!breakdown[type]) {
      breakdown[type] = { 
        count: 0, 
        rate: type === 'non_member' 
          ? lessonPackage.value?.price_non_members 
          : lessonPackage.value?.price_members,
        total: 0 
      }
    }
    breakdown[type].count++
    breakdown[type].total += reg.amount || 0
  })
  
  return breakdown
})

// Computed: Group Financials
const groupFinancials = computed(() => {
  return groups.value.map(group => {
    const groupRegistrations = registrations.value.filter(
      reg => reg.assigned_group_id === group.id
    )
    
    const expectedGroupRevenue = groupRegistrations.reduce(
      (total, reg) => total + (reg.amount || 0), 0
    )
    
    const collectedGroupRevenue = groupRegistrations
      .filter(reg => reg.payment_status === 'paid')
      .reduce((total, reg) => total + (reg.amount_paid || 0), 0)
    
    return {
      id: group.id,
      group_name: group.name,
      trainer_name: group.trainer?.name,
      participants: groupRegistrations.length,
      max_participants: group.max_participants,
      fill_rate: Math.round((groupRegistrations.length / group.max_participants) * 100),
      expected_revenue: expectedGroupRevenue,
      collected_revenue: collectedGroupRevenue,
      outstanding: expectedGroupRevenue - collectedGroupRevenue,
      collection_rate: expectedGroupRevenue > 0 
        ? Math.round((collectedGroupRevenue / expectedGroupRevenue) * 100)
        : 0
    }
  })
})

// Computed: Capacity Metrics
const maxCapacity = computed(() => {
  return groups.value.reduce((total, group) => total + group.max_participants, 0)
})

const currentCapacity = computed(() => {
  return registrations.value.filter(reg => reg.assigned_group_id).length
})

const averageFillRate = computed(() => {
  if (maxCapacity.value === 0) return 0
  return Math.round((currentCapacity.value / maxCapacity.value) * 100)
})

const potentialRevenue = computed(() => {
  if (!lessonPackage.value) return 0
  // Assume 70% members, 30% non-members for calculation
  const memberRevenue = maxCapacity.value * 0.7 * lessonPackage.value.price_members
  const nonMemberRevenue = maxCapacity.value * 0.3 * (lessonPackage.value.price_non_members || lessonPackage.value.price_members)
  return memberRevenue + nonMemberRevenue
})

const missedRevenue = computed(() => {
  return potentialRevenue.value - expectedRevenue.value
})

// Computed: Filtered Registrations
const filteredRegistrations = computed(() => {
  let filtered = [...registrations.value]
  
  if (filterPaymentStatus.value) {
    filtered = filtered.filter(reg => reg.payment_status === filterPaymentStatus.value)
  }
  
  if (filterGroup.value) {
    filtered = filtered.filter(reg => reg.assigned_group_id === filterGroup.value)
  }
  
  return filtered.map(reg => ({
    ...reg,
    amount: reg.user.membership_type === 'non_member'
      ? lessonPackage.value?.price_non_members
      : lessonPackage.value?.price_members
  }))
})

const groupOptions = computed(() => groups.value)

// Table Headers
const groupHeaders = [
  { title: 'Groep', key: 'group_name', sortable: true },
  { title: 'Bezetting', key: 'participants', sortable: true },
  { title: 'Bezettingsgraad', key: 'fill_rate', sortable: true },
  { title: 'Verwacht', key: 'expected_revenue', sortable: true },
  { title: 'Ontvangen', key: 'collected_revenue', sortable: true },
  { title: 'Openstaand', key: 'outstanding', sortable: true },
]

const paymentHeaders = [
  { title: 'Deelnemer', key: 'user', sortable: false },
  { title: 'Groep', key: 'group', sortable: true },
  { title: 'Type', key: 'membership_type', sortable: true },
  { title: 'Bedrag', key: 'amount', sortable: true },
  { title: 'Status', key: 'payment_status', sortable: true },
  { title: 'Betaald op', key: 'paid_at', sortable: true },
  { title: 'Acties', key: 'actions', sortable: false },
]

const paymentStatusOptions = [
  { title: 'Alle', value: null },
  { title: 'Betaald', value: 'paid' },
  { title: 'Onbetaald', value: 'unpaid' },
  { title: 'Terugbetaald', value: 'refunded' },
]

// Methods
const loadData = async () => {
  try {
    loadingGroups.value = true
    loadingRegistrations.value = true
    
    // Load package with all related data
    const response = await axios.get(`/lessons/packages/${packageId}`)
    lessonPackage.value = response.data.data
    groups.value = response.data.data.groups || []
    registrations.value = response.data.data.registrations || []
    
    // Initialize charts after data is loaded
    setTimeout(() => {
      initializeCharts()
    }, 100)
  } catch (error) {
    console.error('Error loading data:', error)
  } finally {
    loadingGroups.value = false
    loadingRegistrations.value = false
  }
}

const initializeCharts = () => {
  // Initialize Payment Status Chart
  if (paymentStatusChart.value) {
    const ctx = paymentStatusChart.value.getContext('2d')
    
    const labels = Object.keys(paymentStatusBreakdown.value).map(status => 
      getPaymentStatusLabel(status)
    )
    const data = Object.values(paymentStatusBreakdown.value).map(item => item.count)
    const colors = Object.keys(paymentStatusBreakdown.value).map(status => 
      getPaymentStatusChartColor(status)
    )
    
    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: data,
          backgroundColor: colors,
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        }
      }
    })
  }
  
  // Initialize Member Type Chart
  if (memberTypeChart.value) {
    const ctx = memberTypeChart.value.getContext('2d')
    
    const labels = Object.keys(memberTypeBreakdown.value).map(type => 
      getMemberTypeLabel(type)
    )
    const data = Object.values(memberTypeBreakdown.value).map(item => item.total)
    
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Omzet',
          data: data,
          backgroundColor: '#2196F3',
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                return '€' + value
              }
            }
          }
        }
      }
    })
  }
}

const formatAmount = (amount) => {
  return new Intl.NumberFormat('nl-NL', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount || 0)
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('nl-BE')
}

const getPaymentStatusColor = (status) => {
  const colors = {
    paid: 'success',
    unpaid: 'warning',
    refunded: 'error',
    pending: 'info'
  }
  return colors[status] || 'default'
}

const getPaymentStatusChartColor = (status) => {
  const colors = {
    paid: '#4CAF50',
    unpaid: '#FF9800',
    refunded: '#F44336',
    pending: '#2196F3'
  }
  return colors[status] || '#9E9E9E'
}

const getPaymentStatusLabel = (status) => {
  const labels = {
    paid: 'Betaald',
    unpaid: 'Onbetaald',
    refunded: 'Terugbetaald',
    pending: 'In afwachting'
  }
  return labels[status] || status
}

const getMemberTypeLabel = (type) => {
  const labels = {
    junior: 'Junior',
    senior: 'Senior',
    veteran: 'Veteraan',
    honorary: 'Erelid',
    non_member: 'Niet-lid'
  }
  return labels[type] || type
}

const getMembershipColor = (type) => {
  const colors = {
    junior: 'success',
    senior: 'primary',
    veteran: 'warning',
    honorary: 'secondary',
    non_member: 'error'
  }
  return colors[type] || 'default'
}

const getFillRateColor = (rate) => {
  if (rate >= 90) return 'success'
  if (rate >= 70) return 'primary'
  if (rate >= 50) return 'warning'
  return 'error'
}

const markAsPaid = async (registration) => {
  if (!confirm('Weet je zeker dat je deze inschrijving als betaald wilt markeren?')) {
    return
  }
  
  try {
    // This would call your API to mark as paid
    await axios.post(`/lessons/registrations/${registration.id}/mark-paid`)
    
    // Reload data
    await loadData()
    alert('Betaling geregistreerd')
  } catch (error) {
    console.error('Error marking as paid:', error)
    alert('Fout bij registreren betaling')
  }
}

const sendPaymentReminders = async () => {
  if (!confirm(`Weet je zeker dat je ${unpaidCount.value} betalingsherinneringen wilt versturen?`)) {
    return
  }
  
  sendingReminders.value = true
  try {
    await axios.post(`/lessons/packages/${packageId}/send-payment-reminders`)
    alert('Herinneringen verstuurd')
  } catch (error) {
    console.error('Error sending reminders:', error)
    alert('Fout bij versturen herinneringen')
  } finally {
    sendingReminders.value = false
  }
}

const sendIndividualReminder = async (registration) => {
  try {
    await axios.post(`/lessons/registrations/${registration.id}/send-reminder`)
    alert('Herinnering verstuurd naar ' + registration.user.name)
  } catch (error) {
    console.error('Error sending reminder:', error)
    alert('Fout bij versturen herinnering')
  }
}

const exportReport = () => {
  // This would generate and download a PDF report
  alert('PDF export komt binnenkort...')
}

// Load Chart.js library
const loadChartJS = () => {
  return new Promise((resolve) => {
    if (window.Chart) {
      resolve()
      return
    }
    
    const script = document.createElement('script')
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js'
    script.onload = resolve
    document.head.appendChild(script)
  })
}

onMounted(async () => {
  await loadChartJS()
  await loadData()
})
</script>

<style scoped>
canvas {
  max-height: 300px !important;
}
</style>
