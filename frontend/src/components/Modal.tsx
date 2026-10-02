import { X } from 'lucide-react';
import { useEffect, type ReactNode } from 'react';

interface ModalProps {
  open: boolean;
  onClose: () => void;
  title: string;
  description?: string;
  children: ReactNode;
  footer?: ReactNode;
  size?: 'sm' | 'md' | 'lg';
}

const SIZE_CLASSES: Record<NonNullable<ModalProps['size']>, string> = {
  sm: 'max-w-md',
  md: 'max-w-lg',
  lg: 'max-w-2xl',
};

export function Modal({ open, onClose, title, description, children, footer, size = 'md' }: ModalProps) {
  useEffect(() => {
    if (!open) return;
    const handler = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose();
    };
    window.addEventListener('keydown', handler);
    document.body.style.overflow = 'hidden';
    return () => {
      window.removeEventListener('keydown', handler);
      document.body.style.overflow = '';
    };
  }, [open, onClose]);

  if (!open) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      {/* Backdrop */}
      <button
        type="button"
        onClick={onClose}
        aria-label="Close"
        className="absolute inset-0 bg-black/50 backdrop-blur-sm"
      />

      {/* Panel */}
      <div className={`relative w-full ${SIZE_CLASSES[size]} bg-white rounded-lg shadow-xl flex flex-col max-h-[90vh]`}>
        <div className="px-6 py-4 border-b border-gray-200 flex items-start justify-between">
          <div>
            <h3 className="text-lg font-semibold">{title}</h3>
            {description && <p className="text-sm text-gray-500 mt-1">{description}</p>}
          </div>
          <button type="button" onClick={onClose} className="text-gray-400 hover:text-gray-600">
            <X className="h-5 w-5" />
          </button>
        </div>

        <div className="flex-1 overflow-y-auto px-6 py-4">{children}</div>

        {footer && (
          <div className="px-6 py-4 border-t border-gray-200 flex justify-end gap-2 bg-gray-50 rounded-b-lg">
            {footer}
          </div>
        )}
      </div>
    </div>
  );
}
