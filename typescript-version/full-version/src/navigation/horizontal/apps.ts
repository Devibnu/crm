export default [
  {
    title: 'Apps',
    icon: { icon: 'tabler-layout-grid-add' },
    children: [
      {
        title: 'Ecommerce',
        icon: { icon: 'tabler-shopping-cart-plus' },
        children: [
          {
            title: 'Dashboard',
            to: 'apps-ecommerce-dashboard',
          },
          {
            title: 'Product',
            children: [
              { title: 'List', to: 'apps-ecommerce-product-list' },
              { title: 'Add', to: 'apps-ecommerce-product-add' },
              { title: 'Category', to: 'apps-ecommerce-product-category-list' },
            ],
          },
          {
            title: 'Order',
            children: [
              { title: 'List', to: 'apps-ecommerce-order-list' },
              { title: 'Details', to: { name: 'apps-ecommerce-order-details-id', params: { id: '9042' } } },
            ],
          },
          {
            title: 'Customer',
            children: [
              { title: 'List', to: 'apps-ecommerce-customer-list' },
              { title: 'Details', to: { name: 'apps-ecommerce-customer-details-id', params: { id: 478426 } } },
            ],
          },
          {
            title: 'Manage Review',
            to: 'apps-ecommerce-manage-review',
          },
          {
            title: 'Referrals',
            to: 'apps-ecommerce-referrals',
          },
          {
            title: 'Settings',
            to: 'apps-ecommerce-settings',
          },
        ],
      },
      {
        title: 'Academy',
        icon: { icon: 'tabler-book' },
        children: [
          { title: 'Dashboard', to: 'apps-academy-dashboard' },
          { title: 'My Course', to: 'apps-academy-my-course' },
          { title: 'Course Details', to: 'apps-academy-course-details' },
        ],
      },
      {
        title: 'Logistics',
        icon: { icon: 'tabler-truck' },
        children: [
          { title: 'Dashboard', to: 'apps-logistics-dashboard' },
          { title: 'Fleet', to: 'apps-logistics-fleet' },
        ],
      },
      {
        title: 'Email',
        icon: { icon: 'tabler-mail' },
        action: 'read',
        subject: 'CrmInbox',
        to: 'apps-email',
      },
      {
        title: 'Chat',
        icon: { icon: 'tabler-message-circle' },
        to: 'apps-chat',
      },
      {
        title: 'Calendar',
        to: 'apps-calendar',
        icon: { icon: 'tabler-calendar' },
      },
      {
        title: 'Kanban',
        icon: { icon: 'tabler-layout-kanban' },
        to: 'apps-kanban',
      },
      {
        title: 'Invoice',
        icon: { icon: 'tabler-file-dollar' },
        action: 'read',
        subject: 'BackofficeInvoice',
        children: [
          { title: 'List', to: 'apps-invoice-list', action: 'read', subject: 'BackofficeInvoice' },
          { title: 'Preview', to: { name: 'apps-invoice-preview-id', params: { id: '5036' } }, action: 'read', subject: 'BackofficeInvoice' },
          { title: 'Edit', to: { name: 'apps-invoice-edit-id', params: { id: '5036' } }, action: 'update', subject: 'BackofficeInvoice' },
          { title: 'Add', to: 'apps-invoice-add', action: 'create', subject: 'BackofficeInvoice' },
        ],
      },
      {
        title: 'User',
        icon: { icon: 'tabler-users' },
        action: 'manage',
        subject: 'Admin',
        children: [
          { title: 'List', to: 'apps-user-list', action: 'manage', subject: 'Admin' },
          { title: 'View', to: { name: 'apps-user-view-id', params: { id: 21 } }, action: 'manage', subject: 'Admin' },
        ],
      },
      {
        title: 'Roles & Permissions',
        icon: { icon: 'tabler-settings' },
        action: 'manage',
        subject: 'Admin',
        children: [
          { title: 'Roles', to: 'apps-roles', action: 'manage', subject: 'Admin' },
          { title: 'Permissions', to: 'apps-permissions', action: 'manage', subject: 'Admin' },
        ],
      },
    ],
  },
]
