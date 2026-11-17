export type WeatherSearchData = {
    city: string;
};

export type Weather = {
    city: string;
    country: string;
    coordinates: {
        latitude: number;
        longitude: number;
    };
    temperature: number | null;
    feels_like: number | null;
    humidity: number | null;
    pressure: number | null;
    wind: {
        speed: number | null;
        direction: number | null;
    };
    conditions: {
        id: number | null;
        main: string | null;
        description: string | null;
        icon: string | null;
    };
    observed_at: string | null;
};

export type WeatherResponse = {
    data: Weather;
};

export type WeatherPageCopy = {
    metaTitle: string;
    home: string;
    eyebrow: string;
    title: string;
    description: string;
    cityLabel: string;
    cityPlaceholder: string;
    show: string;
    loading: string;
    emptyTitle: string;
    emptyDescription: string;
    notFoundTitle: string;
    unavailableTitle: string;
    networkTitle: string;
    networkError: string;
    requestError: string;
    resultAnnouncement: string;
    unavailable: string;
    metrics: {
        feelsLike: string;
        conditions: string;
        humidity: string;
        pressure: string;
        wind: string;
        coordinates: string;
        observedAt: string;
    };
    units: {
        temperature: string;
        humidity: string;
        pressure: string;
        windSpeed: string;
        windDirection: string;
    };
};

export type WeatherPageProps = {
    locale: string;
    copy: WeatherPageCopy;
};

export type WeatherPresentationError = {
    title: string;
    message: string;
};
