<template>
  <VContainer fluid class="pa-6">
    <!-- Statistics Cards -->
    <VRow class="match-height">
      <!-- Total Members Card -->
      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText>
            <div class="d-flex justify-space-between">
              <div>
                <span class="text-caption text-medium-emphasis">Totaal Leden</span>
                <div class="d-flex align-center flex-wrap gap-1 my-1">
                  <h4 class="text-h4">{{ stats.totalMembers }}</h4>
                  <div class="text-success">
                    <VIcon size="24" icon="tabler-trending-up" />
                    <span class="text-sm">+12%</span>
                  </div>
                </div>
                <span class="text-caption text-medium-emphasis">
                  {{ stats.activeMembers }} actief
                </span>
              </div>
              <VAvatar
                size="42"
                variant="tonal"
                color="primary"
                rounded
              >
                <VIcon icon="tabler-users" size="26" />
              </VAvatar>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Upcoming Events Card -->
      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText>
            <div class="d-flex justify-space-between">
              <div>
                <span class="text-caption text-medium-emphasis">Komende Events</span>
                <div class="d-flex align-center flex-wrap gap-1 my-1">
                  <h4 class="text-h4">{{ stats.upcomingEvents }}</h4>
                </div>
                <span class="text-caption text-medium-emphasis">
                  Deze maand
                </span>
              </div>
              <VAvatar
                size="42"
                variant="tonal"
                color="success"
                rounded
              >
                <VIcon icon="tabler-calendar-event" size="26" />
              </VAvatar>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Recent Registrations Card -->
      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText>
            <div class="d-flex justify-space-between">
              <div>
                <span class="text-caption text-medium-emphasis">Nieuwe Inschrijvingen</span>
                <div class="d-flex align-center flex-wrap gap-1 my-1">
                  <h4 class="text-h4">{{ stats.recentRegistrations }}</h4>
                  <div class="text-success">
                    <VIcon size="24" icon="tabler-trending-up" />
                    <span class="text-sm">+25%</span>
                  </div>
                </div>
                <span class="text-caption text-medium-emphasis">
                  Laatste 7 dagen
                </span>
              </div>
              <VAvatar
                size="42"
                variant="tonal"
                color="warning"
                rounded
              >
                <VIcon icon="tabler-user-check" size="26" />
              </VAvatar>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Monthly Revenue Card -->
      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText>
            <div class="d-flex justify-space-between">
              <div>
                <span class="text-caption text-medium-emphasis">Maand Omzet</span>
                <div class="d-flex align-center flex-wrap gap-1 my-1">
                  <h4 class="text-h4">€{{ formatCurrency(stats.monthlyRevenue) }}</h4>
                </div>
                <span class="text-caption text-medium-emphasis">
                  {{ new Date().toLocaleString('nl-BE', { month: 'long' }) }}
                </span>
              </div>
              <VAvatar
                size="42"
                variant="tonal"
                color="info"
                rounded
              >
                <VIcon icon="tabler-currency-euro" size="26" />
              </VAvatar>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Charts Row -->
    <VRow class="match-height mt-6">
      <!-- Revenue Chart -->
      <VCol cols="12" md="8">
        <VCard>
          <VCardItem>
            <VCardTitle>Omzet Overzicht</VCardTitle>
            <VCardSubtitle class="text-medium-emphasis">
              Maandelijkse inkomsten van evenementen en lidmaatschappen
            </VCardSubtitle>
          </VCardItem>
          <VCardText>
            <VueApexCharts
              type="area"
              height="300"
              :options="revenueChartOptions"
              :series="revenueChartSeries"
            />
          </VCardText>
        </VCard>
      </VCol>

      <!-- Member Types Distribution -->
      <VCol cols="12" md="4">
        <VCard>
          <VCardItem>
            <VCardTitle>Leden Verdeling</VCardTitle>
            <VCardSubtitle class="text-medium-emphasis">
              Per lidmaatschap type
            </VCardSubtitle>
          </VCardItem>
          <VCardText>
            <VueApexCharts
              type="donut"
              height="270"
              :options="memberChartOptions"
              :series="memberChartSeries"
            />
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Tables Row -->
    <VRow class="match-height mt-6">
      <!-- Latest News -->
      <VCol cols="12" md="6">
        <VCard>
          <VCardItem>
            <VCardTitle>Recent Nieuws</VCardTitle>
            <template #append>
              <VBtn
                size="small"
                color="primary"
                variant="text"
                :to="{ name: 'news-list' }"
              >
                Bekijk Alle
              </VBtn>
            </template>
          </VCardItem>
          <VDivider />
          <VCardText class="pa-0">
            <VList lines="two">
              <VListItem
                v-for="article in stats.latestNews"
                :key="article.id"
                :to="`/news/${article.id}/edit`"
              >
                <VListItemTitle>
                  {{ article.title }}
                </VListItemTitle>
                <VListItemSubtitle>
                  <div class="d-flex align-center gap-2">
                    <VIcon size="16" icon="tabler-calendar" />
                    {{ formatDate(article.published_at) }}
                    <VIcon size="16" icon="tabler-eye" />
                    {{ article.views }} views
                  </div>
                </VListItemSubtitle>
              </VListItem>
              <VListItem v-if="!stats.latestNews?.length">
                <VListItemTitle class="text-center text-medium-emphasis">
                  Geen nieuws gevonden
                </VListItemTitle>
              </VListItem>
            </VList>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Upcoming Events -->
      <VCol cols="12" md="6">
        <VCard>
          <VCardItem>
            <VCardTitle>Komende Evenementen</VCardTitle>
            <template #append>
              <VBtn
                size="small"
                color="primary"
                variant="text"
                :to="{ name: 'events-list' }"
              >
                Bekijk Alle
              </VBtn>
            </template>
          </VCardItem>
          <VDivider />
          <VCardText class="pa-0">
            <VList lines="two">
              <VListItem
                v-for="event in stats.upcomingEventsList"
                :key="event.id"
                :to="`/events/${event.id}/edit`"
              >
                <template #prepend>
                  <VAvatar
                    size="40"
                    :color="getEventColor(event.type)"
                    variant="tonal"
                  >
                    <VIcon :icon="getEventIcon(event.type)" />
                  </VAvatar>
                </template>
                <VListItemTitle>
                  {{ event.title }}
                </VListItemTitle>
                <VListItemSubtitle>
                  <div class="d-flex align-center gap-2">
                    <VIcon size="16" icon="tabler-calendar" />
                    {{ formatDate(event.start_date) }}
                    <VChip size="x-small" label>
                      {{ event.confirmed_registrations_count || 0 }}/{{ event.max_participants || '∞' }}
                    </VChip>
                  </div>
                </VListItemSubtitle>
              </VListItem>
              <VListItem v-if="!stats.upcomingEventsList?.length">
                <VListItemTitle class="text-center text-medium-emphasis">
                  Geen evenementen gepland
                </VListItemTitle>
              </VListItem>
            </VList>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Recent Members -->
    <VRow class="mt-6">
      <VCol cols="12">
        <VCard>
          <VCardItem>
            <VCardTitle>Nieuwe Leden</VCardTitle>
            <template #append>
              <VBtn
                size="small"
                color="primary"
                variant="text"
                :to="{ name: 'members-list' }"
              >
                Bekijk Alle
              </VBtn>
            </template>
          </VCardItem>
          <VCardText>
            <VTable>
              <thead>
                <tr>
                  <th>Naam</th>
                  <th>Email</th>
                  <th>Lidnummer</th>
                  <th>Type</th>
                  <th>Lid Sinds</th>
                  <th>Acties</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="member in stats.recentMembers" :key="member.id">
                  <td>
                    <div class="d-flex align-center gap-2">
                      <VAvatar size="32">
                        <VImg 
                          v-if="member.avatar" 
                          :src="member.avatar" 
                        />
                        <span v-else>
                          {{ getInitials(member.name) }}
                        </span>
                      </VAvatar>
                      {{ member.name }}
                    </div>
                  </td>
                  <td>{{ member.email }}</td>
                  <td>
                    <VChip size="small" label>
                      {{ member.member_number }}
                    </VChip>
                  </td>
                  <td>
                    <VChip
                      size="small"
                      :color="getMembershipColor(member.membership_type)"
                      label
                    >
                      {{ member.membership_type }}
                    </VChip>
                  </td>
                  <td>{{ formatDate(member.member_since) }}</td>
                  <td>
                    <VBtn
                      icon="tabler-eye"
                      size="small"
                      variant="text"
                      :to="`/members/${member.id}`"
                    />
                  </td>
                </tr>
                <tr v-if="!stats.recentMembers?.length">
                  <td colspan="6" class="text-center text-medium-emphasis">
                    Geen nieuwe leden
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import axios from '@axios'

// Data refs
const stats = ref({
  totalMembers: 0,
  activeMembers: 0,
  upcomingEvents: 0,
  recentRegistrations: 0,
  monthlyRevenue: 0,
  latestNews: [],
  upcomingEventsList: [],
  recentMembers: []
})

// Load dashboard data
const loadDashboardData = async () => {
  try {
    const response = await axios.get('/dashboard/stats')
    stats.value = response.data
  } catch (error) {
    console.error('Failed to load dashboard data:', error)
  }
}

// Revenue Chart Configuration
const revenueChartOptions = {
  chart: {
    type: 'area',
    toolbar: {
      show: false
    },
    sparkline: {
      enabled: false
    }
  },
  colors: ['#666CFF', '#26C6F9'],
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 0.8,
      opacityFrom: 0.7,
      opacityTo: 0.25,
      stops: [0, 95, 100]
    }
  },
  dataLabels: {
    enabled: false
  },
  grid: {
    show: true,
    borderColor: '#f1f1f1',
    strokeDashArray: 5,
    padding: {
      left: 10,
      right: 10
    }
  },
  stroke: {
    width: 2,
    curve: 'smooth'
  },
  xaxis: {
    categories: ['Jan', 'Feb', 'Mrt', 'Apr', 'Mei', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'],
    labels: {
      style: {
        fontSize: '12px',
        colors: '#6e7191'
      }
    }
  },
  yaxis: {
    labels: {
      style: {
        fontSize: '12px',
        colors: '#6e7191'
      },
      formatter: (value) => `€${value}`
    }
  },
  tooltip: {
    theme: 'dark',
    y: {
      formatter: (value) => `€${value}`
    }
  }
}

const revenueChartSeries = ref([
  {
    name: 'Evenementen',
    data: [2800, 4500, 3200, 6700, 4300, 5100, 4900, 6200, 5800, 0, 0, 0]
  },
  {
    name: 'Lidmaatschappen',
    data: [3200, 3100, 3000, 3200, 3100, 3300, 3200, 3100, 3000, 0, 0, 0]
  }
])

// Member Distribution Chart
const memberChartOptions = {
  chart: {
    type: 'donut'
  },
  labels: ['Senior', 'Junior', 'Veteraan', 'Ere-lid'],
  colors: ['#666CFF', '#26C6F9', '#FFAB00', '#72E128'],
  legend: {
    position: 'bottom',
    labels: {
      colors: '#6e7191',
      useSeriesColors: false
    }
  },
  dataLabels: {
    enabled: true,
    formatter: (val) => `${parseInt(val)}%`
  },
  plotOptions: {
    pie: {
      donut: {
        size: '70%',
        labels: {
          show: true,
          total: {
            show: true,
            label: 'Totaal',
            fontSize: '20px',
            color: '#6e7191'
          }
        }
      }
    }
  },
  responsive: [
    {
      breakpoint: 480,
      options: {
        chart: {
          width: 200
        },
        legend: {
          position: 'bottom'
        }
      }
    }
  ]
}

const memberChartSeries = ref([65, 15, 15, 5])

// Helper functions
const formatCurrency = (value) => {
  return new Intl.NumberFormat('nl-BE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(value || 0)
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('nl-BE', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const getInitials = (name) => {
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const getEventIcon = (type) => {
  const icons = {
    tournament: 'tabler-trophy',
    training: 'tabler-ball-tennis',
    social: 'tabler-users-group',
    meeting: 'tabler-briefcase',
    other: 'tabler-calendar'
  }
  return icons[type] || 'tabler-calendar'
}

const getEventColor = (type) => {
  const colors = {
    tournament: 'primary',
    training: 'success',
    social: 'warning',
    meeting: 'info',
    other: 'secondary'
  }
  return colors[type] || 'secondary'
}

const getMembershipColor = (type) => {
  const colors = {
    senior: 'primary',
    junior: 'success',
    veteran: 'warning',
    honorary: 'info',
    non_member: 'secondary'
  }
  return colors[type] || 'secondary'
}

// Load data on mount
onMounted(() => {
  loadDashboardData()
})
</script>

<style lang="scss" scoped>
.match-height {
  > .v-col {
    > .v-card {
      height: 100%;
    }
  }
}
</style>
