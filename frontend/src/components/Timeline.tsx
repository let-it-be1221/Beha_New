import { Check, Clock, X } from 'lucide-react';
import { cn } from '../lib/utils';

export interface TimelineStep {
  label: string;
  description?: string;
  status: 'completed' | 'current' | 'pending' | 'rejected';
  timestamp?: string | null;
}

interface Props {
  steps: TimelineStep[];
}

const ICONS = {
  completed: Check,
  current: Clock,
  pending: Clock,
  rejected: X,
};

const COLORS = {
  completed: 'bg-green-100 text-green-700',
  current: 'bg-blue-100 text-blue-700 ring-2 ring-blue-300',
  pending: 'bg-gray-100 text-gray-400',
  rejected: 'bg-red-100 text-red-700',
};

export function Timeline({ steps }: Props) {
  return (
    <div className="card p-5">
      <ol className="relative">
        {steps.map((step, i) => {
          const Icon = ICONS[step.status];
          const isLast = i === steps.length - 1;
          return (
            <li key={i} className="flex gap-4 pb-6 last:pb-0">
              {/* Vertical line */}
              {!isLast && (
                <span
                  className={cn(
                    'absolute left-[15px] top-8 bottom-0 w-0.5',
                    step.status === 'completed' ? 'bg-green-200' : 'bg-gray-200',
                  )}
                  style={{ height: 'calc(100% - 2rem)' }}
                />
              )}

              {/* Icon */}
              <span
                className={cn(
                  'relative z-10 flex h-8 w-8 items-center justify-center rounded-full flex-shrink-0',
                  COLORS[step.status],
                )}
              >
                <Icon className="h-4 w-4" />
              </span>

              {/* Content */}
              <div className="flex-1 pt-1">
                <p className={cn(
                  'text-sm font-medium',
                  step.status === 'pending' ? 'text-gray-400' : 'text-gray-900',
                )}>
                  {step.label}
                </p>
                {step.description && (
                  <p className="text-xs text-gray-500 mt-0.5">{step.description}</p>
                )}
                {step.timestamp && (
                  <p className="text-xs text-gray-400 mt-1">
                    {new Date(step.timestamp).toLocaleString()}
                  </p>
                )}
              </div>
            </li>
          );
        })}
      </ol>
    </div>
  );
}
