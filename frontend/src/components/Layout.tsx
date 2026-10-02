import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import { useAuth } from '../lib/auth';
import { Building2, Users, FileText, ClipboardList, Workflow as WorkflowIcon, Network, LogOut, Bell } from 'lucide-react';
import { cn } from '../lib/utils';

const NAV = [
  { to: '/dashboard',    label: 'Dashboard',    icon: Building2 },
  { to: '/customers',     label: 'Customers',    icon: Users },
  { to: '/properties',    label: 'Properties',   icon: FileText },
  { to: '/applicants',    label: 'Applicants',    icon: ClipboardList },
  { to: '/workflows',     label: 'Workflows',    icon: WorkflowIcon },
  { to: '/organization', label: 'Organization', icon: Network },
];

export function Layout() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <div className="flex min-h-screen bg-gray-100">
      {/* Sidebar */}
      <aside className="hidden lg:flex w-60 flex-col bg-beha-navy text-white">
        <div className="flex items-center gap-3 px-5 py-5 border-b border-white/10">
          <span className="inline-block h-8 w-8 rounded bg-beha-gold" />
          <span className="text-lg font-semibold tracking-tight">Beha</span>
        </div>
        <nav className="flex-1 px-3 py-4 space-y-1 text-sm">
          {NAV.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              className={({ isActive }) =>
                cn(
                  'flex items-center gap-3 px-3 py-2 rounded',
                  isActive ? 'bg-white/10' : 'hover:bg-white/10',
                )
              }
            >
              <item.icon className="h-4 w-4" />
              <span>{item.label}</span>
            </NavLink>
          ))}
        </nav>
        <div className="px-3 py-4 border-t border-white/10 text-xs text-white/60">
          Beha · v0.1.0-phase1
        </div>
      </aside>

      {/* Main */}
      <div className="flex-1 flex flex-col">
        <header className="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6">
          <div className="text-sm text-gray-500">
            <span className="font-semibold text-gray-900">{user?.username}</span>
            <span className="mx-2">·</span>
            <span>{user?.official_id}</span>
            {user?.roles.length ? (
              <>
                <span className="mx-2">·</span>
                <span className="capitalize">{user.roles.join(', ').replace(/_/g, ' ')}</span>
              </>
            ) : null}
          </div>
          <div className="flex items-center gap-4 text-sm">
            <button className="relative text-gray-700 hover:text-gray-900">
              <Bell className="h-5 w-5" />
            </button>
            <button
              onClick={handleLogout}
              className="flex items-center gap-2 text-red-600 hover:text-red-700"
            >
              <LogOut className="h-4 w-4" />
              <span>Sign out</span>
            </button>
          </div>
        </header>

        <main className="flex-1 p-6 md:p-8">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
