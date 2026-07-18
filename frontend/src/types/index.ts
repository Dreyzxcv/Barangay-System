export interface User {
  id: number;
  name: string;
  email: string;
  role: "resident" | "admin";
  phone?: string;
  address?: string;
  profile_picture?: string;
  is_suspended: boolean;
}

export interface Paginated<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface Announcement {
  id: number;
  title: string;
  content: string;
  category: string;
  image?: string;
  is_pinned: boolean;
  created_at: string;
  author?: { id: number; name: string };
}

export interface Report {
  id: number;
  title: string;
  description: string;
  category: string;
  photo?: string;
  location_address?: string;
  latitude?: number;
  longitude?: number;
  status: string;
  assigned_to?: string;
  admin_notes?: string;
  created_at: string;
  user?: User;
}

export interface ServiceRequest {
  id: number;
  type: string;
  description?: string;
  status: string;
  admin_notes?: string;
  created_at: string;
  user?: User;
}

export interface Event {
  id: number;
  title: string;
  description?: string;
  location?: string;
  start_date: string;
  end_date?: string;
  is_cancelled: boolean;
  creator?: { id: number; name: string };
  rsvps?: { id: number; user_id: number; user?: { id: number; name: string } }[];
}

export interface UserNotification {
  id: number;
  type: string;
  title: string;
  message: string;
  data?: Record<string, unknown>;
  read_at?: string;
  created_at: string;
}

export interface ResidentDashboard {
  announcements: Announcement[];
  active_service_requests: ServiceRequest[];
  submitted_reports: Report[];
  upcoming_events: Event[];
}

export interface AdminDashboard {
  total_residents: number;
  pending_reports: number;
  pending_requests: number;
  active_announcements: number;
  reports_by_status: Record<string, number>;
  requests_by_status: Record<string, number>;
  recent_reports: Report[];
  recent_requests: ServiceRequest[];
}
