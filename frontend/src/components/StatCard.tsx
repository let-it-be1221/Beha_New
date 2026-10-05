import {
  Activity, AlertTriangle, Award, BadgeCheck, Building, Building2, CheckCircle, ClipboardList,
  Clock3, ContactRound, Copy, DollarSign, GitBranch, Globe, Hash, Network, QrCode, UserCheck,
  UserPlus, Users, Workflow,
} from 'lucide-react';
import type { ComponentType, SVGProps } from 'react';
import { formatPrice } from '../lib/utils';
import type { DashboardStat } from '../types';

type IconType = ComponentType<SVGProps<SVGSVGElement>>;

const ICON_MAP: Record<string, IconType> = {
  users: Users,
  'users-2': Users,
  'user-check': UserCheck,
  'user-clock': Clock3,
  'user-plus': UserPlus,
  contact: UserCheck,
  network: Network,
  'git-branch': GitBranch,
  building: Building,
  'building-2': Building2,
  workflow: Workflow,
  'alert-triangle': AlertTriangle,
  'clipboard-list': ClipboardList,
  activity: Activity,
  'check-circle': CheckCircle,
  globe: Globe,
  'dollar-sign': DollarSign,
  copy: Copy,
  'id-card': ContactRound,
  hash: Hash,
  'qr-code': QrCode,
  award: Award,
  badge: BadgeCheck,
};

export function StatCard({ stat }: { stat: DashboardStat }) {
  const Icon = ICON_MAP[stat.icon] ?? Activity;
  const displayValue = stat.is_currency ? formatPrice(Number(stat.value)) : String(stat.value);

  return (
    <div className="card p-5">
      <div className="flex items-start justify-between">
        <div>
          <p className="text-xs uppercase tracking-wide text-gray-500">{stat.label}</p>
          <p className="text-2xl font-bold mt-1">{displayValue}</p>
        </div>
        <div className="rounded-md bg-beha-navy/10 p-2">
          <Icon className="h-5 w-5 text-beha-navy" />
        </div>
      </div>
    </div>
  );
}
