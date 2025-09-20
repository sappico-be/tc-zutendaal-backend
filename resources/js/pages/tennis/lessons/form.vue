<template>
  <VContainer>
    <VRow>
      <VCol cols="12">
        <VCard>
          <VCardItem>
            <VCardTitle>
              {{ isEdit ? 'Lessenpakket Bewerken' : 'Nieuw Lessenpakket' }}
            </VCardTitle>
          </VCardItem>
          
          <VCardText>
            <VForm @submit.prevent="savePackage">
              <VRow>
                <VCol cols="12" md="8">
                  <VTextField
                    v-model="packageData.name"
                    label="Naam"
                    required
                  />
                </VCol>
                
                <VCol cols="12" md="4">
                  <VSelect
                    v-model="packageData.status"
                    label="Status"
                    :items="statusOptions"
                  />
                </VCol>
                
                <VCol cols="12">
                  <VTextarea
                    v-model="packageData.description"
                    label="Beschrijving"
                    rows="3"
                  />
                </VCol>
                
                <VCol cols="12" md="4">
                  <VTextField
                    v-model.number="packageData.total_lessons"
                    label="Aantal lessen"
                    type="number"
                    required
                  />
                </VCol>
                
                <VCol cols="12" md="4">
                  <VTextField
                    v-model.number="packageData.min_participants"
                    label="Min. deelnemers"
                    type="number"
                  />
                </VCol>
                
                <VCol cols="12" md="4">
                  <VTextField
                    v-model.number="packageData.max_participants"
                    label="Max. deelnemers"
                    type="number"
                  />
                </VCol>
                
                <VCol cols="12" md="4">
                  <VTextField
                    v-model="packageData.start_date"
                    label="Start datum"
                    type="date"
                    required
                  />
                </VCol>
                
                <VCol cols="12" md="4">
                  <VTextField
                    v-model="packageData.end_date"
                    label="Eind datum"
                    type="date"
                    required
                  />
                </VCol>
                
                <VCol cols="12" md="4">
                  <VTextField
                    v-model="packageData.registration_deadline"
                    label="Inschrijf deadline"
                    type="date"
                    required
                  />
                </VCol>
                
                <VCol cols="12" md="6">
                  <VTextField
                    v-model.number="packageData.price_members"
                    label="Prijs leden (€)"
                    type="number"
                    step="0.01"
                    required
                  />
                </VCol>
                
                <VCol cols="12" md="6">
                  <VTextField
                    v-model.number="packageData.price_non_members"
                    label="Prijs niet-leden (€)"
                    type="number"
                    step="0.01"
                  />
                </VCol>
                
                <VCol cols="12">
                  <p class="text-body-1 mb-2">Beschikbare dagen:</p>
                  <VCheckbox
                    v-for="day in weekDays"
                    :key="day.value"
                    v-model="packageData.available_days"
                    :label="day.label"
                    :value="day.value"
                    inline
                  />
                </VCol>
                
                <VCol cols="12">
                  <VBtn
                    type="submit"
                    color="primary"
                    class="mr-3"
                    :loading="loading"
                  >
                    Opslaan
                  </VBtn>
                  <VBtn
                    variant="outlined"
                    :to="{ name: 'tennis-lessons-packages' }"
                  >
                    Annuleren
                  </VBtn>
                </VCol>
              </VRow>
            </VForm>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/plugins/axios'

definePage({
  meta: {
    requiresAuth: true,
  },
})

const route = useRoute()
const router = useRouter()
const isEdit = ref(false)
const loading = ref(false)

const packageData = ref({
  name: '',
  description: '',
  total_lessons: 10,
  start_date: '',
  end_date: '',
  registration_deadline: '',
  price_members: 0,
  price_non_members: 0,
  status: 'draft',
  min_participants: null,
  max_participants: null,
  available_days: [],
})

const statusOptions = [
  { title: 'Concept', value: 'draft' },
  { title: 'Open', value: 'open' },
  { title: 'Gesloten', value: 'closed' },
  { title: 'Afgelopen', value: 'completed' },
]

const weekDays = [
  { label: 'Maandag', value: 'monday' },
  { label: 'Dinsdag', value: 'tuesday' },
  { label: 'Woensdag', value: 'wednesday' },
  { label: 'Donderdag', value: 'thursday' },
  { label: 'Vrijdag', value: 'friday' },
  { label: 'Zaterdag', value: 'saturday' },
  { label: 'Zondag', value: 'sunday' },
]

const savePackage = async () => {
  loading.value = true
  try {
    if (isEdit.value) {
      await axios.put(`/lessons/packages/${route.params.id}`, packageData.value)
    } else {
      await axios.post('/lessons/packages', packageData.value)
    }
    router.push({ name: 'tennis-lessons-packages' })
  } catch (error) {
    console.error('Error saving package:', error)
    alert('Error bij het opslaan')
  } finally {
    loading.value = false
  }
}

const loadPackage = async () => {
  if (route.params.id) {
    isEdit.value = true
    try {
      const response = await axios.get(`/lessons/packages/${route.params.id}`)
      packageData.value = response.data.data
    } catch (error) {
      console.error('Error loading package:', error)
    }
  }
}

onMounted(() => {
  loadPackage()
})
</script>
