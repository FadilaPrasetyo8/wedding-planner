export type WeddingRole = 'owner' | 'editor' | 'viewer';

export type WeddingSummary = {
    uuid: string;
    name: string;
    partnerNames: string;
    role: WeddingRole;
};

export type ActiveWedding = {
    uuid: string;
    name: string;
    partnerNames: string;
    weddingDate: string;
};

export type WeddingCapabilities = {
    manageMembers: boolean;
    editWedding: boolean;
    editOperationalData: boolean;
};
