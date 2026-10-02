import { Link } from 'react-router-dom';
import { ChevronRight, Inbox } from 'lucide-react';
import type { DashboardPendingItem } from '../types';

interface Props {
  title: string;
  items: DashboardPendingItem[];
  emptyMessage?: string;
}

export function PendingList({ title, items, emptyMessage }: Props) {
  return (
    <div className="card">
      <div className="px-5 py-3 border-b border-gray-200">
        <h2 className="font-semibold">{title}</h2>
        <p className="text-xs text-gray-500 mt-0.5">{items.length} item(s) awaiting your action</p>
      </div>
      <ul className="divide-y divide-gray-100">
        {items.length === 0 ? (
          <li className="px-5 py-8 text-center text-sm text-gray-500 flex flex-col items-center gap-2">
            <Inbox className="h-6 w-6 text-gray-300" />
            {emptyMessage ?? 'Nothing pending. You are all caught up.'}
          </li>
        ) : (
          items.map((item) => (
            <li key={item.id}>
              <Link to={item.route} className="flex items-center justify-between px-5 py-3 hover:bg-gray-50">
                <div className="min-w-0">
                  <p className="font-medium text-gray-900 truncate">{item.title}</p>
                  <p className="text-xs text-gray-500 font-mono">{item.subtitle}</p>
                  <p className="text-xs text-gray-500 mt-0.5">{item.meta}</p>
                </div>
                <ChevronRight className="h-4 w-4 text-gray-400 flex-shrink-0 ml-3" />
              </Link>
            </li>
          ))
        )}
      </ul>
    </div>
  );
}
