import type { LucideIcon } from 'lucide-react';
import {
    Clock3,
    CloudSun,
    Compass,
    Droplets,
    Gauge,
    MapPin,
    ThermometerSun,
    Wind,
} from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { Weather, WeatherPageCopy } from '@/pages/weather/types';

type WeatherResultProps = {
    weather: Weather;
    locale: string;
    copy: WeatherPageCopy;
};

type WeatherMetricProps = {
    icon: LucideIcon;
    label: string;
    value: string;
};

function WeatherMetric({ icon: Icon, label, value }: WeatherMetricProps) {
    return (
        <div className="rounded-xl border border-border/70 bg-background/55 p-4 shadow-xs dark:bg-background/65">
            <div className="mb-3 flex items-center gap-2 text-muted-foreground">
                <Icon className="size-4" aria-hidden="true" />
                <span className="text-xs font-medium tracking-wide uppercase">
                    {label}
                </span>
            </div>
            <p className="text-base font-semibold break-words text-foreground">
                {value}
            </p>
        </div>
    );
}

export function WeatherResult({ weather, locale, copy }: WeatherResultProps) {
    const numberFormatter = new Intl.NumberFormat(locale, {
        maximumFractionDigits: 1,
    });
    const coordinateFormatter = new Intl.NumberFormat(locale, {
        maximumFractionDigits: 2,
    });
    const dateFormatter = new Intl.DateTimeFormat(locale, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });

    const formatNumber = (value: number | null, unit: string): string =>
        value === null
            ? copy.unavailable
            : `${numberFormatter.format(value)} ${unit}`;

    const condition =
        weather.conditions.description ??
        weather.conditions.main ??
        copy.unavailable;

    const wind = [
        weather.wind.speed === null
            ? null
            : `${numberFormatter.format(weather.wind.speed)} ${copy.units.windSpeed}`,
        weather.wind.direction === null
            ? null
            : `${numberFormatter.format(weather.wind.direction)} ${copy.units.windDirection}`,
    ]
        .filter((value): value is string => value !== null)
        .join(' · ');

    const observedAt = weather.observed_at
        ? dateFormatter.format(new Date(weather.observed_at))
        : copy.unavailable;

    return (
        <Card className="overflow-hidden border-primary/15 bg-card/85 shadow-xl shadow-primary/5 backdrop-blur dark:border-primary/20 dark:bg-card/90">
            <CardHeader className="gap-5 border-b border-border/60 pb-6 sm:flex-row sm:items-start sm:justify-between">
                <div className="space-y-2">
                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <MapPin className="size-4" aria-hidden="true" />
                        <span>{weather.country}</span>
                    </div>
                    <CardTitle className="text-3xl tracking-tight sm:text-4xl">
                        {weather.city}
                    </CardTitle>
                    <CardDescription className="flex items-center gap-2 text-base capitalize">
                        <CloudSun className="size-5" aria-hidden="true" />
                        {condition}
                    </CardDescription>
                </div>
                <p className="text-5xl font-semibold tracking-tighter text-primary sm:text-6xl">
                    {formatNumber(weather.temperature, copy.units.temperature)}
                </p>
            </CardHeader>
            <CardContent className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <WeatherMetric
                    icon={ThermometerSun}
                    label={copy.metrics.feelsLike}
                    value={formatNumber(
                        weather.feels_like,
                        copy.units.temperature,
                    )}
                />
                <WeatherMetric
                    icon={CloudSun}
                    label={copy.metrics.conditions}
                    value={condition}
                />
                <WeatherMetric
                    icon={Droplets}
                    label={copy.metrics.humidity}
                    value={formatNumber(weather.humidity, copy.units.humidity)}
                />
                <WeatherMetric
                    icon={Gauge}
                    label={copy.metrics.pressure}
                    value={formatNumber(weather.pressure, copy.units.pressure)}
                />
                <WeatherMetric
                    icon={Wind}
                    label={copy.metrics.wind}
                    value={wind || copy.unavailable}
                />
                <WeatherMetric
                    icon={Compass}
                    label={copy.metrics.coordinates}
                    value={`${coordinateFormatter.format(weather.coordinates.latitude)}, ${coordinateFormatter.format(weather.coordinates.longitude)}`}
                />
                <div className="sm:col-span-2 lg:col-span-3">
                    <WeatherMetric
                        icon={Clock3}
                        label={copy.metrics.observedAt}
                        value={observedAt}
                    />
                </div>
            </CardContent>
        </Card>
    );
}
