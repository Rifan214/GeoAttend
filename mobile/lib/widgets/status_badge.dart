import 'package:flutter/material.dart';
import '../core/constants/app_colors.dart';

class StatusBadge extends StatelessWidget {
  final String? status; // 'present', 'late', 'incomplete', null

  const StatusBadge({super.key, this.status});

  @override
  Widget build(BuildContext context) {
    final s = status?.toLowerCase();

    Color bgColor;
    Color textColor;
    String label;
    IconData icon;

    if (s == 'present') {
      bgColor = AppColors.emeraldLight;
      textColor = AppColors.emeraldDark;
      label = 'Hadir Tepat Waktu (Present)';
      icon = Icons.check_circle_outline;
    } else if (s == 'late') {
      bgColor = AppColors.amberLight;
      textColor = AppColors.amberDark;
      label = 'Terlambat (Late)';
      icon = Icons.access_time;
    } else if (s == 'incomplete') {
      bgColor = AppColors.roseLight;
      textColor = AppColors.roseDark;
      label = 'Belum Selesai (Incomplete)';
      icon = Icons.warning_amber_rounded;
    } else {
      bgColor = AppColors.slateLight;
      textColor = AppColors.slateDark;
      label = 'Belum Ada Presensi';
      icon = Icons.schedule;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: textColor.withValues(alpha: 0.3)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: textColor),
          const SizedBox(width: 6),
          Flexible(
            child: Text(
              label,
              style: TextStyle(
                color: textColor,
                fontSize: 12,
                fontWeight: FontWeight.w600,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }
}
