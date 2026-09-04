import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 35.25 6.42 21.67A9.56 9.56 0 0 1 20 8.22a9.56 9.56 0 0 1 13.58 13.45L20 35.25Zm-7-24.5a5.95 5.95 0 0 0-4.23 10.14L20 32.12l11.23-11.23A5.96 5.96 0 0 0 22.8 12.46L20 15.26l-2.8-2.8A5.92 5.92 0 0 0 13 10.75Z" />
        </svg>
    );
}
