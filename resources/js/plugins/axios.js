import axios from 'axios'

const axiosIns = axios.create({
  baseURL: '/api',
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
  },
})

// Add request interceptor to add token
axiosIns.interceptors.request.use(config => {
  // Check localStorage first (voor compatibility)
  let token = localStorage.getItem('accessToken')
  
  // Als niet in localStorage, check cookies
  if (!token) {
    const cookies = document.cookie.split(';')
    const tokenCookie = cookies.find(c => c.trim().startsWith('accessToken='))
    if (tokenCookie) {
      token = decodeURIComponent(tokenCookie.split('=')[1])
    }
  }
  
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export default axiosIns
