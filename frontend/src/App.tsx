import { Navigate, Route, Routes } from 'react-router-dom';
import { useAuth } from './lib/auth';
import { Layout } from './components/Layout';
import { LoginPage } from './pages/LoginPage';
import { DashboardPage } from './pages/DashboardPage';
import { ForcePasswordChangePage } from './pages/ForcePasswordChangePage';
import { CustomersListPage } from './pages/CustomersListPage';
import { CustomerCreatePage } from './pages/CustomerCreatePage';
import { CustomerDetailPage } from './pages/CustomerDetailPage';
import { PropertiesListPage } from './pages/PropertiesListPage';
import { PropertyCreatePage } from './pages/PropertyCreatePage';
import { PropertyDetailPage } from './pages/PropertyDetailPage';
import { ApplicantsListPage } from './pages/ApplicantsListPage';
import { ApplicantDetailPage } from './pages/ApplicantDetailPage';
import { WorkflowsListPage } from './pages/WorkflowsListPage';
import { WorkflowDetailPage } from './pages/WorkflowDetailPage';
import { OrganizationPage } from './pages/OrganizationPage';
import { NotFoundPage } from './pages/NotFoundPage';
import type { JSX } from 'react';

/** Require auth — redirect to /login if unauthenticated. */
function Protected({ children }: { children: JSX.Element }) {
  const { isAuthenticated, loading, mustChangePassword } = useAuth();

  if (loading) {
    return (
      <div className="flex h-screen items-center justify-center text-gray-500">
        Loading…
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  if (mustChangePassword && !window.location.pathname.startsWith('/force-password-change')) {
    return <Navigate to="/force-password-change" replace />;
  }

  return children;
}

/** Public route — redirect to /dashboard if already authenticated. */
function PublicOnly({ children }: { children: JSX.Element }) {
  const { isAuthenticated, loading, mustChangePassword } = useAuth();
  if (loading) return <div className="flex h-screen items-center justify-center">Loading…</div>;
  if (isAuthenticated && !mustChangePassword) return <Navigate to="/dashboard" replace />;
  return children;
}

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<PublicOnly><LoginPage /></PublicOnly>} />
      <Route path="/force-password-change" element={<Protected><ForcePasswordChangePage /></Protected>} />

      <Route
        path="/"
        element={
          <Protected>
            <Layout />
          </Protected>
        }
      >
        <Route index element={<Navigate to="/dashboard" replace />} />
        <Route path="dashboard" element={<DashboardPage />} />
        <Route path="customers" element={<CustomersListPage />} />
        <Route path="customers/new" element={<CustomerCreatePage />} />
        <Route path="customers/:id" element={<CustomerDetailPage />} />
        <Route path="properties" element={<PropertiesListPage />} />
        <Route path="properties/new" element={<PropertyCreatePage />} />
        <Route path="properties/:id" element={<PropertyDetailPage />} />
        <Route path="applicants" element={<ApplicantsListPage />} />
        <Route path="applicants/:id" element={<ApplicantDetailPage />} />
        <Route path="workflows" element={<WorkflowsListPage />} />
        <Route path="workflows/:id" element={<WorkflowDetailPage />} />
        <Route path="organization" element={<OrganizationPage />} />
      </Route>

      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  );
}
