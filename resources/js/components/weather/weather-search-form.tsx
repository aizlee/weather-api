import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { WeatherPageCopy } from '@/pages/weather/types';

type WeatherSearchFormProps = {
    city: string;
    error?: string;
    processing: boolean;
    copy: WeatherPageCopy;
    onCityChange: (city: string) => void;
    onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};

export function WeatherSearchForm({
    city,
    error,
    processing,
    copy,
    onCityChange,
    onSubmit,
}: WeatherSearchFormProps) {
    return (
        <form className="space-y-4" onSubmit={onSubmit}>
            <div className="space-y-2">
                <Label htmlFor="city">{copy.cityLabel}</Label>
                <div className="flex flex-col gap-3 sm:flex-row">
                    <Input
                        id="city"
                        name="city"
                        type="search"
                        value={city}
                        maxLength={255}
                        autoComplete="address-level2"
                        autoFocus
                        placeholder={copy.cityPlaceholder}
                        aria-invalid={Boolean(error)}
                        aria-describedby={error ? 'city-error' : undefined}
                        className="h-11 bg-background/80 sm:flex-1 dark:bg-background/70"
                        onChange={(event) => onCityChange(event.target.value)}
                    />
                    <Button
                        type="submit"
                        size="lg"
                        disabled={processing}
                        className="min-w-28"
                    >
                        {processing ? (
                            <Spinner aria-label={copy.loading} />
                        ) : null}
                        {copy.show}
                    </Button>
                </div>
                <InputError id="city-error" message={error} />
            </div>
        </form>
    );
}
