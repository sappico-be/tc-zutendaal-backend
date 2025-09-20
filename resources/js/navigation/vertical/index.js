// resources/js/navigation/vertical/index.js

export default [
  // ========================================
  // TENNIS CLUB TC ZUTENDAAL
  // ========================================
    {
        title: 'Dashboard',
        to: { name: 'dashboard-tennis' },
        icon: { icon: 'tabler-smart-home' },
        badgeContent: 'TC',
        badgeClass: 'bg-primary',
    },

    { heading: 'Tennis Club' },
  
    {
        title: 'Nieuws',
        icon: { icon: 'tabler-news' },
        children: [
            {
                title: 'Overzicht',
                to: { name: 'tennis-news-list' },
            },
            {
                title: 'Nieuw Artikel',
                to: { name: 'tennis-news-create' },
            },
        ],
    },
    {
        title: 'Evenementen',
        icon: { icon: 'tabler-calendar-event' },
        children: [
            {
                title: 'Overzicht',
                to: { name: 'tennis-events-list' },
            },
            // {
            // title: 'Nieuw Event',
            // to: { name: 'tennis-events-create' },
            // },
        ],
    },
    {
        title: 'Lessen',
        icon: { icon: 'tabler-school' },
        children: [
            {
                title: 'Pakketten',
                to: { name: 'tennis-lessons-packages' },
            }
        ],
    },
    //   {
    //     title: 'Leden',
    //     to: { name: 'tennis-members-list' },
    //     icon: { icon: 'tabler-users' },
    //   },
    //   {
    //     title: 'Betalingen',
    //     to: { name: 'tennis-payments-list' },
    //     icon: { icon: 'tabler-currency-euro' },
    //   },
  
  // ========================================
  // ORIGINAL VUEXY ITEMS (optional - kun je later verwijderen)
  // ========================================
  
  { heading: 'Settings' },
  
  {
    title: 'Account Settings',
    to: { name: 'pages-account-settings-tab', params: { tab: 'account' } },
    icon: { icon: 'tabler-settings' },
  },
]
