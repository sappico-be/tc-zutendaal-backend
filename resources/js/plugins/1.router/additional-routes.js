// resources/js/plugins/1.router/additional-routes.js

const emailRouteComponent = () => import('@/pages/apps/email/index.vue')

// 👉 Redirects
export const redirects = [
  // ℹ️ We are redirecting to different pages based on role.
  // NOTE: Role is just for UI purposes. ACL is based on abilities.
  {
    path: '/',
    name: 'index',
    redirect: to => {
      // Redirect naar Tennis Club Dashboard voor nu
      return { name: 'dashboard-tennis' }
      
      // Original redirect logic (kun je later weer activeren)
      // const userData = useCookie('userData')
      // const userRole = userData.value?.role
      // if (userRole === 'admin')
      //   return { name: 'dashboard-tennis' }
      // if (userRole === 'client')
      //   return { name: 'access-control' }
      // return { name: 'login', query: to.query }
    },
  },
  {
    path: '/pages/user-profile',
    name: 'pages-user-profile',
    redirect: () => ({ name: 'pages-user-profile-tab', params: { tab: 'profile' } }),
  },
  {
    path: '/pages/account-settings',
    name: 'pages-account-settings',
    redirect: () => ({ name: 'pages-account-settings-tab', params: { tab: 'account' } }),
  },
]

export const routes = [
  // ========================================
  // TENNIS CLUB ROUTES
  // ========================================
  
  // Tennis Club Dashboard
  {
    path: '/dashboard',
    name: 'dashboard-tennis',
    component: () => import('@/pages/dashboard-tennis.vue'),
    meta: {
      navActiveLink: 'dashboard-tennis',
    },
  },
  
  // News Management
  {
    path: '/tennis/news',
    name: 'tennis-news-list',
    component: () => import('@/pages/tennis/news/list.vue'),
    meta: {
      navActiveLink: 'tennis-news',
    },
  },
  {
    path: '/tennis/news/create',
    name: 'tennis-news-create',
    component: () => import('@/pages/tennis/news/form.vue'),
    meta: {
      navActiveLink: 'tennis-news',
    },
  },
  {
    path: '/tennis/news/:id/edit',
    name: 'tennis-news-edit',
    component: () => import('@/pages/tennis/news/form.vue'),
    meta: {
      navActiveLink: 'tennis-news',
    },
  },
  
  // Event Management
  {
    path: '/tennis/events',
    name: 'tennis-events-list',
    component: () => import('@/pages/tennis/events/list.vue'),
    meta: {
      navActiveLink: 'tennis-events',
    },
  },
  {
    path: '/tennis/events/create',
    name: 'tennis-events-create',
    component: () => import('@/pages/tennis/events/form.vue'),
    meta: {
      navActiveLink: 'tennis-events',
    },
  },
  {
    path: '/tennis/events/:id/edit',
    name: 'tennis-events-edit',
    component: () => import('@/pages/tennis/events/form.vue'),
    meta: {
      navActiveLink: 'tennis-events',
    },
  },
  {
    path: '/tennis/events/:id/registrations',
    name: 'tennis-event-registrations',
    component: () => import('@/pages/tennis/events/registrations.vue'),
    meta: {
      navActiveLink: 'tennis-events',
    },
  },

  // Lessons Management
  {
    path: '/tennis/lessons',
    name: 'tennis-lessons-packages',
    component: () => import('@/pages/tennis/lessons/packages.vue'),
    meta: {
      navActiveLink: 'tennis-lessons',
    },
  },
  {
    path: '/tennis/lessons/create',
    name: 'tennis-lessons-create',
    component: () => import('@/pages/tennis/lessons/form.vue'),
    meta: {
      navActiveLink: 'tennis-lessons',
    },
  },
  {
    path: '/tennis/lessons/:id/edit',
    name: 'tennis-lessons-edit',
    component: () => import('@/pages/tennis/lessons/form.vue'),
    meta: {
      navActiveLink: 'tennis-lessons',
    },
  },
  {
    path: '/tennis/lessons/:id/manage',
    name: 'tennis-lessons-manage',
    component: () => import('@/pages/tennis/lessons/manage.vue'),
    meta: {
      navActiveLink: 'tennis-lessons',
    },
  },
  {
    path: '/tennis/lessons/:id/schedule',
    name: 'tennis-lessons-schedule',
    component: () => import('@/pages/tennis/lessons/schedule.vue'),
    meta: {
      navActiveLink: 'tennis-lessons',
    },
  },
  {
    path: '/tennis/lessons/:id/attendance',
    name: 'tennis-lessons-attendance',
    component: () => import('@/pages/tennis/lessons/attendance-stats.vue'),
    meta: {
      navActiveLink: 'tennis-lessons',
    },
  },

  {
    path: '/tennis/lessons/:id/financial-report',
    name: 'tennis-lessons-financial',
    component: () => import('@/pages/tennis/lessons/financial-report.vue'),
    meta: {
      navActiveLink: 'tennis-lessons',
    },
  },
  
  // // Member Management
  // {
  //   path: '/tennis/members',
  //   name: 'tennis-members-list',
  //   component: () => import('@/pages/tennis/members/list.vue'),
  //   meta: {
  //     navActiveLink: 'tennis-members',
  //   },
  // },
  // {
  //   path: '/tennis/members/:id',
  //   name: 'tennis-member-detail',
  //   component: () => import('@/pages/tennis/members/detail.vue'),
  //   meta: {
  //     navActiveLink: 'tennis-members',
  //   },
  // },
  
  // // Payment Management
  // {
  //   path: '/tennis/payments',
  //   name: 'tennis-payments-list',
  //   component: () => import('@/pages/tennis/payments/list.vue'),
  //   meta: {
  //     navActiveLink: 'tennis-payments',
  //   },
  // },
  {
    path: '/tennis/payments/success/:registrationId',
    name: 'tennis-payment-success',
    component: () => import('@/pages/tennis/payments/success.vue'),
    meta: {
      navActiveLink: 'tennis-events',
    },
  },
  
  // ========================================
  // ORIGINAL VUEXY ROUTES (behouden)
  // ========================================
  
  // Email filter
  {
    path: '/apps/email/filter/:filter',
    name: 'apps-email-filter',
    component: emailRouteComponent,
    meta: {
      navActiveLink: 'apps-email',
      layoutWrapperClasses: 'layout-content-height-fixed',
    },
  },
  // Email label
  {
    path: '/apps/email/label/:label',
    name: 'apps-email-label',
    component: emailRouteComponent,
    meta: {
      // contentClass: 'email-application',
      navActiveLink: 'apps-email',
      layoutWrapperClasses: 'layout-content-height-fixed',
    },
  },
  {
    path: '/dashboards/logistics',
    name: 'dashboards-logistics',
    component: () => import('@/pages/apps/logistics/dashboard.vue'),
  },
  {
    path: '/dashboards/academy',
    name: 'dashboards-academy',
    component: () => import('@/pages/apps/academy/dashboard.vue'),
  },
  {
    path: '/apps/ecommerce/dashboard',
    name: 'apps-ecommerce-dashboard',
    component: () => import('@/pages/dashboards/ecommerce.vue'),
  },
]
