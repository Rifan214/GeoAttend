import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:geoattend/widgets/status_badge.dart';

void main() {
  Widget buildTestableWidget(Widget child) {
    return MaterialApp(
      home: Scaffold(
        body: Center(child: child),
      ),
    );
  }

  testWidgets('renders Present badge correctly', (tester) async {
    await tester.pumpWidget(buildTestableWidget(const StatusBadge(status: 'present')));
    expect(find.text('Hadir Tepat Waktu (Present)'), findsOneWidget);
    expect(find.byIcon(Icons.check_circle_outline), findsOneWidget);
  });

  testWidgets('renders Late badge correctly', (tester) async {
    await tester.pumpWidget(buildTestableWidget(const StatusBadge(status: 'late')));
    expect(find.text('Terlambat (Late)'), findsOneWidget);
    expect(find.byIcon(Icons.access_time), findsOneWidget);
  });

  testWidgets('renders Incomplete badge correctly', (tester) async {
    await tester.pumpWidget(buildTestableWidget(const StatusBadge(status: 'incomplete')));
    expect(find.text('Belum Selesai (Incomplete)'), findsOneWidget);
    expect(find.byIcon(Icons.warning_amber_rounded), findsOneWidget);
  });

  testWidgets('renders default unrecorded badge when status is null', (tester) async {
    await tester.pumpWidget(buildTestableWidget(const StatusBadge(status: null)));
    expect(find.text('Belum Ada Presensi'), findsOneWidget);
  });
}
