import { initMobileSidebar } from './modules/mobile-sidebar';
import { initNotificationMenu } from './modules/notifications';
import { initPageSkeleton } from './modules/page-skeleton';
import { initPublicGallery } from './modules/public-gallery';
import { initPublicNavigation } from './modules/public-navigation';
import { initPublicPdfInputs } from './modules/public-pdf-inputs';
import { initSortableTables } from './modules/sortable-tables';
import { initThemeToggle } from './modules/theme';
import { initAdminForms } from './modules/admin-forms';
import { initLiveSearch } from './modules/live-search';

initThemeToggle();

document.addEventListener('DOMContentLoaded', initSortableTables);
document.addEventListener('DOMContentLoaded', initMobileSidebar);
document.addEventListener('DOMContentLoaded', initPageSkeleton);
document.addEventListener('DOMContentLoaded', initNotificationMenu);
document.addEventListener('DOMContentLoaded', initAdminForms);
document.addEventListener('DOMContentLoaded', initLiveSearch);
document.addEventListener('DOMContentLoaded', initPublicGallery);
document.addEventListener('DOMContentLoaded', initPublicNavigation);
document.addEventListener('DOMContentLoaded', initPublicPdfInputs);
