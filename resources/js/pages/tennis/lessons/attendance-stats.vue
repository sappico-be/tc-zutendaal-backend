<template>
  <VContainer>
    <!-- Header -->
    <VRow>
      <VCol cols="12">
        <div class="d-flex justify-space-between align-center mb-6">
          <div>
            <h1 class="text-h4">Aanwezigheid Statistieken</h1>
            <p class="text-body-1 mt-1" v-if="lessonPackage">{{ lessonPackage.name }}</p>
          </div>
          <VBtn 
            variant="outlined"
            :to="{ name: 'tennis-lessons-packages' }"
          >
            Terug
          </VBtn>
        </div>
      </VCol>
    </VRow>

    <!-- Overall Statistics -->
    <VRow>
      <VCol cols="12">
        <VCard>
          <VCardItem>
            <VCardTitle>Algemene Statistieken</VCardTitle>
          </VCardItem>
          <VCardText>
            <VRow>
              <VCol cols="12" md="3">
                <div class="text-center">
                  <div class="text-h3 text-primary">{{ packageStats.average_attendance_rate }}%</div>
                  <div class="text-body-2">Gemiddelde Aanwezigheid</div>
                </div>
              </VCol>
              <VCol cols="12" md="3">
                <div class="text-center">
                  <div class="text-h3">{{ packageStats.total_lessons_given }}</div>
                  <div class="text-body-2">Lessen Gegeven</div>
                </div>
              </VCol>
              <VCol cols="12" md="3">
                <div class="text-center">
                  <div class="text-h3">{{ packageStats.by_status?.present || 0 }}</div>
                  <div class="text-body-2">Totaal Aanwezig</div>
                </div>
              </VCol>
              <VCol cols="12" md="3">
                <div class="text-center">
                  <div class="text-h3">{{ packageStats.by_status?.absent || 0 }}</div>
                  <div class="text-body-2">Totaal Afwezig</div>
                </div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Group Tabs -->
    <VRow class="mt-6">
      <VCol cols="12">
        <VCard>
          <VTabs v-model="selectedGroup" class="border-bottom">
            <VTab 
              v-for="group in groups" 
              :key="group.id"
              :value="group.id"
            >
              {{ group.name }}
            </VTab>
          </VTabs>
          
          <VCardText v-if="selectedGroup">
            <!-- Group Statistics Table -->
            <VDataTable
              :headers="headers"
              :items="groupStats"
              :loading="loadingStats"
            >
              <template #item.attendance_rate="{ item }">
                <div class="d-flex align-center">
                  <VProgressLinear
                    :model-value="item.attendance_rate"
                    :color="getAttendanceColor(item.attendance_rate)"
                    height="20"
                    class="me-3"
                    style="min-width: 100px"
                  >
                    {{ item.attendance_rate }}%
                  </VProgressLinear>
                </div>
              </template>
              
              <template #item.present="{ item }">
                <VChip color="success" size="small">
                  {{ item.present }}
                </VChip>
              </template>
              
              <template #item.late="{ item }">
                <VChip color="warning" size="small">
                  {{ item.late }}
                </VChip>
              </template>
              
              <template #item.excused="{ item }">
                <VChip color="info" size="small">
                  {{ item.excused }}
                </VChip>
              </template>
              
              <template #item.absent="{ item }">
                <VChip color="error" size="small">
                  {{ item.absent }}
                </VChip>
              </template>
              
              <template #item.actions="{ item }">
                <VBtn
                  size="small"
                  variant="text"
                  @click="showUserDetail(item)"
                >
                  Details
                </VBtn>
              </template>
            </VDataTable>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- User Detail Dialog -->
    <VDialog v-model="showDetailDialog" max-width="600">
      <VCard>
        <VCardTitle>
          Aanwezigheid Details: {{ selectedUser?.user_name }}
        </VCardTitle>
        <VCardText>
          <VRow>
            <VCol cols="12">
              <VList>
                <VListItem>
                  <template #prepend>
                    <VIcon color="success">tabler-check</VIcon>
                  </template>
                  <VListItemTitle>Aanwezig</VListItemTitle>
                  <template #append>
                    <VListItemTitle>{{ selectedUser?.present }} lessen</VListItemTitle>
                  </template>
                </VListItem>
                
                <VListItem>
                  <template #prepend>
                    <VIcon color="warning">tabler-clock</VIcon>
                  </template>
                  <VListItemTitle>Te laat</VListItemTitle>
                  <template #append>
                    <VListItemTitle>{{ selectedUser?.late }} lessen</VListItemTitle>
                  </template>
                </VListItem>
                
                <VListItem>
                  <template #prepend>
                    <VIcon color="info">tabler-mail</VIcon>
                  </template>
                  <VListItemTitle>Afgemeld</VListItemTitle>
                  <template #append>
                    <VListItemTitle>{{ selectedUser?.excused }} lessen</VListItemTitle>
                  </template>
                </VListItem>
                
                <VListItem>
                  <template #prepend>
                    <VIcon color="error">tabler-x</VIcon>
                  </template>
                  <VListItemTitle>Afwezig</VListItemTitle>
                  <template #append>
                    <VListItemTitle>{{ selectedUser?.absent }} lessen</VListItemTitle>
                  </template>
                </VListItem>
              </VList>
            </VCol>
          </VRow>
          
          <VDivider class="my-4" />
          
          <div class="text-center">
            <div class="text-h4 text-primary">{{ selectedUser?.attendance_rate }}%</div>
            <div class="text-body-1">Aanwezigheidspercentage</div>
          </div>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn @click="showDetailDialog = false">Sluiten</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </VContainer>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from '@/plugins/axios'

definePage({
  meta: {
    requiresAuth: true,
  },
})

const route = useRoute()
const packageId = route.params.id

const lessonPackage = ref(null)
const groups = ref([])
const selectedGroup = ref(null)
const groupStats = ref([])
const packageStats = ref({})
const loadingStats = ref(false)
const showDetailDialog = ref(false)
const selectedUser = ref(null)

const headers = [
  { title: 'Naam', key: 'user_name' },
  { title: 'Aanwezigheid %', key: 'attendance_rate', width: 200 },
  { title: 'Aanwezig', key: 'present' },
  { title: 'Te laat', key: 'late' },
  { title: 'Afgemeld', key: 'excused' },
  { title: 'Afwezig', key: 'absent' },
  { title: 'Totaal', key: 'total_lessons' },
  { title: '', key: 'actions', sortable: false },
]

const getAttendanceColor = (rate) => {
  if (rate >= 90) return 'success'
  if (rate >= 75) return 'primary'
  if (rate >= 60) return 'warning'
  return 'error'
}

const loadPackageData = async () => {
  try {
    // Load package with groups
    const packageResponse = await axios.get(`/lessons/packages/${packageId}`)
    lessonPackage.value = packageResponse.data.data
    groups.value = packageResponse.data.data.groups || []
    
    // Select first group by default
    if (groups.value.length > 0) {
      selectedGroup.value = groups.value[0].id
    }
    
    // Load overall package stats
    const statsResponse = await axios.get(`/lessons/packages/${packageId}/attendance-stats`)
    packageStats.value = statsResponse.data.data
  } catch (error) {
    console.error('Error loading package data:', error)
  }
}

const loadGroupStats = async () => {
  if (!selectedGroup.value) return
  
  loadingStats.value = true
  try {
    const response = await axios.get(`/lessons/packages/${packageId}/groups/${selectedGroup.value}/attendance-stats`)
    groupStats.value = response.data.data
  } catch (error) {
    console.error('Error loading group stats:', error)
    groupStats.value = []
  } finally {
    loadingStats.value = false
  }
}

const showUserDetail = (user) => {
  selectedUser.value = user
  showDetailDialog.value = true
}

watch(selectedGroup, () => {
  loadGroupStats()
})

onMounted(() => {
  loadPackageData()
})
</script>
