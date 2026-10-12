class WorkSchedule {
  final int id;
  final String name;
  final String startTime;
  final String endTime;
  final int lateToleranceMinutes;

  WorkSchedule({
    required this.id,
    required this.name,
    required this.startTime,
    required this.endTime,
    required this.lateToleranceMinutes,
  });

  factory WorkSchedule.fromJson(Map<String, dynamic> json) {
    return WorkSchedule(
      id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
      name: json['name'] as String? ?? '',
      startTime: json['start_time'] as String? ?? '08:00',
      endTime: json['end_time'] as String? ?? '17:00',
      lateToleranceMinutes: json['late_tolerance_minutes'] is int
          ? json['late_tolerance_minutes']
          : int.parse(json['late_tolerance_minutes'].toString()),
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'start_time': startTime,
        'end_time': endTime,
        'late_tolerance_minutes': lateToleranceMinutes,
      };
}
