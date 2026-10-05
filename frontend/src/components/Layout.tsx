import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import { useAuth } from '../lib/auth';
import {
  Building2, Users, FileText, ClipboardList, Workflow as WorkflowIcon, Network,
  LogOut, Bell,
} from 'lucide-react';
import { cn } from '../lib/utils';

/**
 * Role-based menu items.
 * Each menu item specifies which roles can see it. '*' means all authenticated users.
 *
 * Spec §21 — show users only information relevant to their role.
 * Spec §29 — do not overload the interface.
 */
interface MenuItem {
  to: string;
  label: string;
  icon: React.ComponentType<{ className?: string }>;
  roles: string[] | ['*'];
}

const ALL_MENU_ITEMS: MenuItem[] = [
  {
    to: '/dashboard',
    label: 'Dashboard',
    icon: Building2,
    roles: ['*'],
  },
  {
    to: '/customers',
    label: 'Customers',
    icon: Users,
    roles: ['team_member', 'team_leader', 'record_officer', 'executive_officer', 'finance_officer', 'system_administrator'],
  },
  {
    to: '/properties',
    label: 'Properties',
    icon: FileText,
    roles: ['generation_leader', 'executive_officer', 'record_officer', 'finance_officer', 'system_administrator', 'team_member'],
  },
  {
    to: '/applicants',
    label: 'Applicants',
    icon: ClipboardList,
    roles: ['team_leader', 'branch_leader', 'record_officer', 'system_administrator', 'executive_officer'],
  },
  {
    to: '/workflows',
    label: 'Workflows',
    icon: WorkflowIcon,
    roles: ['*'],
  },
  {
    to: '/organization',
    label: 'Organization',
    icon: Network,
    roles: ['system_administrator', 'executive_officer', 'generation_leader', 'branch_leader', 'team_leader', 'finance_officer'],
  },
];

/** Filter menu items based on the user's roles. */
function visibleMenuItems(userRoles: string[]): MenuItem[] {
  return ALL_MENU_ITEMS.filter((item) => {
    if (item.roles.includes('*')) return true;
    return item.roles.some((role) => userRoles.includes(role));
  });
}

export function Layout() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const userRoles = user?.roles ?? [];
  const menuItems = visibleMenuItems(userRoles);

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
          {menuItems.map((item) => (
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
          Beha · v0.1.0-phase7
        </div>
      </aside>

      {/* Main */}
      <div className="flex-1 flex flex-col">
        <header className="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6">
          <div className="flex items-center gap-3 text-sm">
            <button className="lg:hidden text-neutral-700" aria-label="Open menu">≡</button>
            <div className="text-gray-500">
              <span className="font-semibold text-gray-900">{user?.username}</span>
              <span className="mx-2">·</span>
              <span className="font-mono text-xs">{user?.official_id}</span>
              {user?.roles.length ? (
                <>
                  <span className="mx-2">·</span>
                  <span className="capitalize text-xs">
                    {user.roles.join(', ').replace(/_/g, ' ')}
                  </span>
                </>
              ) : null}
            </div>
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

        {/* Mobile menu (horizontal scroll) */}
        <div className="lg:hidden bg-beha-navy text-white px-3 py-2 flex gap-1 overflow-x-auto">
          {menuItems.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              className={({ isActive }) =>
                cn(
                  'flex items-center gap-2 px-3 py-2 rounded text-sm whitespace-nowrap',
                  isActive ? 'bg-white/10' : 'hover:bg-white/10',
                )
              }
            >
              <item.icon className="h-4 w-4" />
              <span>{item.label}</span>
            </NavLink>
          ))}
        </div>

        <main className="flex-1 p-6 md:p-8">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
