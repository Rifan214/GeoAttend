class User {
  final int id;
  final String name;
  final String email;
  final String role;
  final int? workLocationId;
  final bool isActive;
  final String? createdAt;

  User({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    this.workLocationId,
    required this.isActive,
    this.createdAt,
  });

  bool get isEmployee => role.toLowerCase() == 'employee';
  bool get isAdmin => role.toLowerCase() == 'admin';

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
      name: json['name'] as String? ?? '',
      email: json['email'] as String? ?? '',
      role: json['role'] as String? ?? 'employee',
      workLocationId: json['work_location_id'] != null
          ? (json['work_location_id'] is int
              ? json['work_location_id']
              : int.tryParse(json['work_location_id'].toString()))
          : null,
      isActive: json['is_active'] == true || json['is_active'] == 1,
      createdAt: json['created_at'] as String?,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'email': email,
        'role': role,
        'work_location_id': workLocationId,
        'is_active': isActive,
        'created_at': createdAt,
      };
}
