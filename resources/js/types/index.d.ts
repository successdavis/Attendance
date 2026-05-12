import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
    role: string | null;
    permissions: string[];
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    flash: {
        success: string | null;
        error: string | null;
        newDeviceToken: string | null;
        newDeviceName: string | null;
    };
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    role: string;
    status: 'active' | 'inactive' | 'suspended';
    branch: string | null;
    notes: string | null;
    policy_id: number | null;
    external_student_id: string | null;
    external_synced_at: string | null;
    program_expires_at: string | null;
    profile_photo_path: string | null;
    profile_photo_url: string;        // computed by model accessor, always a string
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface UserCredential {
    id: number;
    user_id: number;
    type: 'rfid' | 'fingerprint' | 'face';
    value: string;
    device_id: number | null;
    label: string | null;
    enrolled_at: string;
    enrolled_by: number;
    is_active: boolean;
    revoked_at: string | null;
    revoked_by: number | null;
    device?: AttendanceDevice;
    enrolledBy?: User;
}

export interface AttendanceDevice {
    id: number;
    device_uid: string;
    name: string;
    type: 'rfid_reader' | 'fingerprint_scanner' | 'face_camera' | 'multi';
    location: string | null;
    branch: string | null;
    ip_address: string | null;
    status: 'active' | 'inactive' | 'maintenance';
    last_seen_at: string | null;
    last_ip: string | null;
    firmware_version: string | null;
    registered_by: number;
    registeredBy?: User;
    created_at: string;
    updated_at: string;
}

export interface AttendancePolicy {
    id: number;
    name: string;
    description: string | null;
    sign_in_start: string | null;
    sign_in_end: string | null;
    sign_out_start: string | null;
    sign_out_end: string | null;
    grace_period_minutes: number | null;
    late_threshold_minutes: number | null;
    allow_multiple_daily: boolean;
    weekend_allowed: boolean;
    time_restriction_enabled: boolean;
    is_default: boolean;
    is_active: boolean;
    users_count?: number;
    created_at: string;
    updated_at: string;
}

export interface AttendanceSetting {
    id: number;
    key: string;
    value: string | null;
    type: 'string' | 'integer' | 'boolean' | 'time' | 'json' | 'array';
    group: string;
    label: string;
    description: string | null;
}

export interface AttendanceLog {
    id: number;
    user_id: number;
    method: 'rfid' | 'fingerprint' | 'face' | null;
    identifier: string | null;
    status: 'check_in' | 'check_out';
    logged_at: string;
    location: string | null;
    is_manual: boolean;
    override_reason: string | null;
    device_id: number | null;
    user?: User;
    device?: AttendanceDevice;
}

export interface StudentSyncLog {
    id: number;
    triggered_by: 'schedule' | 'manual';
    triggered_by_user: number | null;
    started_at: string;
    finished_at: string | null;
    status: 'running' | 'completed' | 'failed' | 'partial';
    total_fetched: number;
    total_created: number;
    total_updated: number;
    total_deactivated: number;
    total_skipped: number;
    error_message: string | null;
    triggeredByUser?: User;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
}

export type BreadcrumbItemType = BreadcrumbItem;
