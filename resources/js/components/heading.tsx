export default function Heading({
    title,
    description,
    variant = 'default',
}: {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
}) {
    return (
        <header className={variant === 'small' ? '' : 'mb-8 space-y-0.5'}>
            <h2
                className={
                    variant === 'small'
                        ? 'mb-0.5 text-base font-medium text-nopal-700'
                        : 'text-xl font-semibold tracking-tight text-nopal-700'
                }
            >
                {title}
            </h2>
            {description && (
                <p className="text-sm text-stone-600">{description}</p>
            )}
        </header>
    );
}
