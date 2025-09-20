<template>
  <VContainer>
    <!-- Page Header -->
    <VRow>
      <VCol cols="12">
        <div class="d-flex justify-space-between align-center mb-6">
          <h1 class="text-h4">Nieuws Beheer</h1>
          <VBtn 
            color="primary" 
            prepend-icon="tabler-plus"
            :to="{ name: 'tennis-news-create' }"
            >
            Nieuw Artikel
          </VBtn>
        </div>
      </VCol>
    </VRow>

    <!-- News Table -->
    <VRow>
      <VCol cols="12">
        <VCard>
          <VCardText>
            <VDataTable
              :headers="headers"
              :items="newsItems"
              :loading="loading"
              :items-per-page="10"
            >
              <template #item.status="{ item }">
                <VChip
                  :color="getStatusColor(item.status)"
                  size="small"
                >
                  {{ item.status }}
                </VChip>
              </template>
              
              <template #item.published_at="{ item }">
                {{ formatDate(item.published_at) }}
              </template>
              
              <template #item.actions="{ item }">
                <VBtn
                  icon="tabler-edit"
                  size="small"
                  variant="text"
                  class="mr-1"
                  :to="{ name: 'tennis-news-edit', params: { id: item.id } }"
                />
                <VBtn
                  icon="tabler-trash"
                  size="small"
                  variant="text"
                  color="error"
                  @click="deleteArticle(item)"
                />
              </template>
            </VDataTable>
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

const loading = ref(false)
const newsItems = ref([])

const headers = [
  { title: 'Titel', key: 'title' },
  { title: 'Status', key: 'status' },
  { title: 'Gepubliceerd', key: 'published_at' },
  { title: 'Views', key: 'views' },
  { title: 'Acties', key: 'actions', sortable: false },
]

const loadNews = async () => {
  loading.value = true
  try {
    const response = await axios.get('/news')
    newsItems.value = response.data.data
  } catch (error) {
    console.error('Error loading news:', error)
  } finally {
    loading.value = false
  }
}

const getStatusColor = (status) => {
  const colors = {
    draft: 'warning',
    published: 'success',
    archived: 'secondary',
  }
  return colors[status] || 'default'
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('nl-BE')
}

const deleteArticle = async (item) => {
  if (confirm(`Weet je zeker dat je "${item.title}" wilt verwijderen?`)) {
    try {
      await axios.delete(`/news/${item.id}`)
      await loadNews() // Herlaad de lijst
    } catch (error) {
      console.error('Error deleting article:', error)
    }
  }
}

onMounted(() => {
  loadNews()
})
</script>
