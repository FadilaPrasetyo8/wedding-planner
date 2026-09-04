import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import PasswordInput from '@/components/password-input';

describe('PasswordInput', () => {
    it('toggles password visibility with an accessible control', () => {
        render(<PasswordInput aria-label="Password" />);

        const input = screen.getByLabelText('Password');
        const toggle = screen.getByRole('button', { name: 'Show password' });

        expect(input).toHaveAttribute('type', 'password');

        fireEvent.click(toggle);

        expect(input).toHaveAttribute('type', 'text');
        expect(
            screen.getByRole('button', { name: 'Hide password' }),
        ).toBeInTheDocument();
    });
});
