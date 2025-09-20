<template>
  <VContainer>
    <!-- Page Header -->
    <VRow>
      <VCol cols="12">
        <div class="d-flex justify-space-between align-center mb-6">
          <h1 class="text-h4">Lessenpakketten</h1>
          <VBtn 
            color="primary" 
            prepend-icon="tabler-plus"
            :to="{ name: 'tennis-lessons-create' }"
          >
            Nieuw Pakket
          </VBtn>
        </div>
      </VCol>
    </VRow>

    <!-- Packages Table -->
    <VRow>
      <VCol cols="12">
        <VCard>
          <VCardText>
            <VTable>
              <thead>
                <tr>
                  <th>Naam</th>
                  <th>Periode</th>
                  <th>Lessen</th>
                  <th>Prijs</th>
                  <th>Inschrijvingen</th>
                  <th>Status</th>
                  <th>Acties</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="pkg in packages" :key="pkg.id">
                  <td>{{ pkg.name }}</td>
                  <td>
                    {{ formatDate(pkg.start_date) }} - {{ formatDate(pkg.end_date) }}
                  </td>
                  <td>{{ pkg.total_lessons }}</td>
                  <td>€{{ pkg.price_members }}</td>
                  <td>{{ pkg.registrations_count || 0 }}</td>
                  <td>
                    <VChip 
                      :color="getStatusColor(pkg.status)"
                      size="small"
                    >
                      {{ pkg.status }}
                    </VChip>
                  </td>
                  <td>
                    <VBtn
                        icon="tabler-calendar"
                        size="small"
                        variant="text"
                        color="success"
                        :to="{ name: 'tennis-lessons-schedule', params: { id: pkg.id } }"
                        title="Rooster"
                    />
                    <VBtn
                        icon="tabler-users"
                        size="small"
                        variant="text"
                        :to="{ name: 'tennis-lessons-manage', params: { id: pkg.id } }"
                        title="Groepen beheren"
                    />
                    <VBtn
                        icon="tabler-edit"
                        size="small"
                        variant="text"
                        :to="{ name: 'tennis-lessons-edit', params: { id: pkg.id } }"
                        title="Bewerken"
                    />
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
import { ref, onMounted } from 'vue'
import axios from '@/plugins/axios'

definePage({
  meta: {
    requiresAuth: true,
  },
})

const packages = ref([])

const loadPackages = async () => {
  try {
    const response = await axios.get('/lessons/packages')
    packages.value = response.data.data
  } catch (error) {
    console.error('Error loading packages:', error)
  }
}

const getStatusColor = (status) => {
  const colors = {
    draft: 'warning',
    open: 'success',
    closed: 'error',
    completed: 'secondary',
  }
  return colors[status] || 'default'
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('nl-BE')
}

onMounted(() => {
  loadPackages()
})
</script>
