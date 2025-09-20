<template>
  <VContainer>
    <VRow>
      <VCol cols="12">
        <VCard>
          <VCardItem>
            <VCardTitle>
              {{ isEdit ? 'Nieuws Bewerken' : 'Nieuw Artikel' }}
            </VCardTitle>
          </VCardItem>
          
          <VCardText>
            <VForm @submit.prevent="saveArticle">
              <VRow>
                <VCol cols="12">
                  <VTextField
                    v-model="article.title"
                    label="Titel"
                    required
                  />
                </VCol>
                
                <VCol cols="12">
                  <VTextarea
                    v-model="article.excerpt"
                    label="Samenvatting"
                    rows="2"
                  />
                </VCol>
                
                <VCol cols="12">
                  <VTextarea
                    v-model="article.content"
                    label="Inhoud"
                    rows="10"
                    required
                  />
                </VCol>
                
                <VCol cols="12" md="6">
                  <VSelect
                    v-model="article.status"
                    label="Status"
                    :items="statusOptions"
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
                    :to="{ name: 'tennis-news-list' }"
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

const article = ref({
  title: '',
  excerpt: '',
  content: '',
  status: 'draft',
})

const statusOptions = [
  { title: 'Concept', value: 'draft' },
  { title: 'Gepubliceerd', value: 'published' },
  { title: 'Gearchiveerd', value: 'archived' },
]

const saveArticle = async () => {
  loading.value = true
  try {
    if (isEdit.value) {
      await axios.put(`/news/${route.params.id}`, article.value)
    } else {
      await axios.post('/news', article.value)
    }
    router.push({ name: 'tennis-news-list' })
  } catch (error) {
    console.error('Error saving article:', error)
  } finally {
    loading.value = false
  }
}

const loadArticle = async () => {
  if (route.params.id) {
    isEdit.value = true
    try {
      const response = await axios.get(`/news/${route.params.id}`)
      article.value = response.data.data
    } catch (error) {
      console.error('Error loading article:', error)
    }
  }
}

onMounted(() => {
  loadArticle()
})
</script>
