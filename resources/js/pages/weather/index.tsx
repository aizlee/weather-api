import { Head, Link, useHttp } from '@inertiajs/react';
import { ArrowLeft, CloudSun, Search, TriangleAlert } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { WeatherResult } from '@/components/weather/weather-result';
import { WeatherSearchForm } from '@/components/weather/weather-search-form';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { home } from '@/routes';
import { show as showWeather } from '@/routes/weather';
import type {
    Weather,
    WeatherPageProps,
    WeatherPresentationError,
    WeatherResponse,
    WeatherSearchData,
} from './types';

const HTTP_NOT_FOUND = 404;
const HTTP_SERVICE_UNAVAILABLE = 503;

function responseMessage(data: string): string | null {
    try {
        const payload: unknown = JSON.parse(data);

        if (
            typeof payload === 'object' &&
            payload !== null &&
            'message' in payload &&
            typeof payload.message === 'string'
        ) {
            return payload.message;
        }
    } catch {
        return null;
    }

    return null;
}

export default function WeatherPage({ locale, copy }: WeatherPageProps) {
    const [weather, setWeather] = useState<Weather | null>(null);
    const [requestError, setRequestError] =
        useState<WeatherPresentationError | null>(null);
    const { data, setData, errors, processing, get, clearErrors } = useHttp<
        WeatherSearchData,
        WeatherResponse
    >({
        city: '',
    });
    const errorTitlesByStatus: Partial<Record<number, string>> = {
        [HTTP_NOT_FOUND]: copy.notFoundTitle,
        [HTTP_SERVICE_UNAVAILABLE]: copy.unavailableTitle,
    };

    const handleSubmit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();

        if (processing) {
            return;
        }

        void get(showWeather.url(), {
            onBefore: () => {
                setWeather(null);
                setRequestError(null);
                clearErrors();
            },
            onSuccess: (response) => {
                setWeather(response.data);
            },
            onError: () => {
                setWeather(null);
                setRequestError(null);
            },
            onHttpException: (response) => {
                setWeather(null);

                setRequestError({
                    title:
                        errorTitlesByStatus[response.status] ??
                        copy.unavailableTitle,
                    message:
                        responseMessage(response.data) ?? copy.requestError,
                });
            },
            onNetworkError: () => {
                setWeather(null);
                setRequestError({
                    title: copy.networkTitle,
                    message: copy.networkError,
                });
            },
        }).catch(() => undefined);
    };

    return (
        <>
            <Head title={copy.metaTitle} />
            <div className="relative min-h-screen overflow-x-hidden bg-background text-foreground">
                <div
                    className="pointer-events-none absolute inset-x-0 top-0 z-0 h-[32rem] bg-gradient-to-b from-primary/10 via-chart-2/5 to-transparent"
                    aria-hidden="true"
                />
                <header className="relative z-10 mx-auto flex w-full max-w-6xl items-center px-5 py-6 sm:px-8 lg:px-10">
                    <Link
                        href={home()}
                        className="inline-flex items-center gap-2 rounded-md text-sm font-medium text-muted-foreground transition-colors outline-none hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <ArrowLeft className="size-4" aria-hidden="true" />
                        {copy.home}
                    </Link>
                </header>

                <main className="relative z-10 mx-auto w-full max-w-6xl px-5 pb-16 sm:px-8 lg:px-10 lg:pb-24">
                    <section className="grid items-start gap-8 pt-8 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:gap-12 lg:pt-16">
                        <div className="space-y-8 lg:sticky lg:top-10">
                            <div className="space-y-4">
                                <div className="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-3 py-1 text-xs font-semibold tracking-[0.18em] text-primary uppercase">
                                    <CloudSun
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    {copy.eyebrow}
                                </div>
                                <h1 className="max-w-xl text-4xl font-semibold tracking-tight text-balance sm:text-5xl lg:text-6xl">
                                    {copy.title}
                                </h1>
                                <p className="max-w-xl text-base leading-7 text-muted-foreground sm:text-lg">
                                    {copy.description}
                                </p>
                            </div>

                            <Card className="border-primary/15 bg-card/85 shadow-lg shadow-primary/5 backdrop-blur dark:border-primary/20 dark:bg-card/90">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-lg">
                                        <Search
                                            className="size-5 text-primary"
                                            aria-hidden="true"
                                        />
                                        {copy.cityLabel}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <WeatherSearchForm
                                        city={data.city}
                                        error={errors.city}
                                        processing={processing}
                                        copy={copy}
                                        onCityChange={(city) =>
                                            setData('city', city)
                                        }
                                        onSubmit={handleSubmit}
                                    />
                                </CardContent>
                            </Card>
                        </div>

                        <section aria-live="polite" aria-busy={processing}>
                            {processing ? (
                                <Card className="bg-card/80 shadow-xl backdrop-blur">
                                    <CardHeader>
                                        <CardTitle>{copy.loading}</CardTitle>
                                        <CardDescription>
                                            <Skeleton className="mt-2 h-4 w-2/3" />
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="grid gap-3 sm:grid-cols-2">
                                        <Skeleton className="h-28 sm:col-span-2" />
                                        <Skeleton className="h-24" />
                                        <Skeleton className="h-24" />
                                        <Skeleton className="h-24" />
                                        <Skeleton className="h-24" />
                                    </CardContent>
                                </Card>
                            ) : null}

                            {!processing && requestError ? (
                                <Alert
                                    variant="destructive"
                                    className="bg-destructive/10 dark:bg-destructive/15"
                                >
                                    <TriangleAlert aria-hidden="true" />
                                    <AlertTitle>
                                        {requestError.title}
                                    </AlertTitle>
                                    <AlertDescription>
                                        {requestError.message}
                                    </AlertDescription>
                                </Alert>
                            ) : null}

                            {!processing && !requestError && weather ? (
                                <>
                                    <span className="sr-only">
                                        {copy.resultAnnouncement}
                                    </span>
                                    <WeatherResult
                                        weather={weather}
                                        locale={locale}
                                        copy={copy}
                                    />
                                </>
                            ) : null}

                            {!processing && !requestError && !weather ? (
                                <Card className="border-dashed bg-card/55 text-center shadow-none backdrop-blur dark:bg-card/65">
                                    <CardContent className="flex min-h-80 flex-col items-center justify-center px-8 py-12">
                                        <div className="mb-5 flex size-16 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                            <CloudSun
                                                className="size-8"
                                                aria-hidden="true"
                                            />
                                        </div>
                                        <CardTitle className="text-xl">
                                            {copy.emptyTitle}
                                        </CardTitle>
                                        <CardDescription className="mt-2 max-w-sm leading-6">
                                            {copy.emptyDescription}
                                        </CardDescription>
                                    </CardContent>
                                </Card>
                            ) : null}
                        </section>
                    </section>
                </main>
            </div>
        </>
    );
}
